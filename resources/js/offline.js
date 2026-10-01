/*
 * Offline support: IndexedDB outbox, offline form queueing and background sync.
 */

const DB_NAME = 'maternal-health-db';
const DB_VERSION = 1;
const OUTBOX_STORE = 'outbox';

/* ---------------------------------- IndexedDB ---------------------------------- */

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;
            if (!db.objectStoreNames.contains(OUTBOX_STORE)) {
                const store = db.createObjectStore(OUTBOX_STORE, { keyPath: 'id', autoIncrement: true });
                store.createIndex('status', 'status', { unique: false });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

function outboxStore(db, mode) {
    return db.transaction(OUTBOX_STORE, mode).objectStore(OUTBOX_STORE);
}

/*
 * Staff member signed in on this page (only staff can sync). Items are tagged with who entered
 * them and only synced by that person, so a visit logged by one midwife on a shared tablet is
 * never recorded under the next one to sign in.
 */
function currentUserId() {
    const id = document.querySelector('meta[name="offline-user-id"]')?.getAttribute('content');
    return id ? Number(id) : null;
}

// Items queued before entries were tagged have no owner; any staff member may sync those.
function isOwnItem(item) {
    return item.user_id == null || item.user_id === currentUserId();
}

function getAllPendingItems() {
    return openDb().then((db) =>
        new Promise((resolve, reject) => {
            const items = [];
            const request = outboxStore(db, 'readonly').index('status').openCursor(IDBKeyRange.only('pending'));

            request.onsuccess = () => {
                const cursor = request.result;
                if (cursor) {
                    items.push(cursor.value);
                    cursor.continue();
                } else {
                    db.close();
                    resolve(items);
                }
            };
            request.onerror = () => {
                db.close();
                reject(request.error);
            };
        })
    );
}

function getPendingItems() {
    return getAllPendingItems().then((items) => items.filter(isOwnItem));
}

function countPendingItems() {
    return getPendingItems().then((items) => items.length);
}

function addOutboxItem(item) {
    return openDb().then((db) =>
        new Promise((resolve, reject) => {
            const request = outboxStore(db, 'readwrite').add(item);
            request.onsuccess = () => {
                const id = request.result;
                db.close();
                resolve(id);
            };
            request.onerror = () => {
                db.close();
                reject(request.error);
            };
        })
    );
}

function deleteOutboxItems(ids) {
    return openDb().then((db) =>
        new Promise((resolve, reject) => {
            const tx = db.transaction(OUTBOX_STORE, 'readwrite');
            const store = tx.objectStore(OUTBOX_STORE);
            ids.forEach((id) => store.delete(id));
            tx.oncomplete = () => {
                db.close();
                resolve();
            };
            tx.onerror = () => {
                db.close();
                reject(tx.error);
            };
        })
    );
}

/* ---------------------------------- Helpers ---------------------------------- */

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/*
 * Convert FormData into a plain object. Keys like "medical_history[Hypertension]"
 * are nested into objects so the server receives a normal array.
 */
function formDataToObject(formData) {
    const result = {};

    formData.forEach((value, key) => {
        const matches = key.match(/^([^\[]+)((?:\[[^\]]*\])*)$/);
        if (!matches) {
            result[key] = value;
            return;
        }

        const [, root, brackets] = matches;
        if (!brackets) {
            result[root] = value;
            return;
        }

        let current = result[root];
        if (current === undefined || typeof current !== 'object') {
            current = {};
            result[root] = current;
        }

        const parts = brackets.match(/\[[^\]]*\]/g);
        parts.forEach((part, index) => {
            const keyPart = part.slice(1, -1);
            const isLast = index === parts.length - 1;
            if (isLast) {
                current[keyPart] = value;
            } else {
                if (current[keyPart] === undefined || typeof current[keyPart] !== 'object') {
                    current[keyPart] = {};
                }
                current = current[keyPart];
            }
        });
    });

    return result;
}

/*
 * Random v4 UUID. The server keeps the UUIDs it has applied, so retrying an item never creates it twice.
 */
function generateUuid() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID();
    }

    const bytes = window.crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function queueItem(type, data) {
    return addOutboxItem({
        uuid: generateUuid(),
        type,
        status: 'pending',
        user_id: currentUserId(),
        created_at: new Date().toISOString(),
        data,
    }).then(() => {
        updateOfflineBadge();
        registerBackgroundSync();
        // If we are already online, try to sync immediately.
        if (navigator.onLine) {
            syncPending();
        }
    });
}

function queuePatientRegistration(formData) {
    return queueItem('patient_registration', formDataToObject(formData));
}

/* ---------------------------------- Sync ---------------------------------- */

async function refreshCsrfToken() {
    const response = await fetch('/sync/token', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Unable to refresh CSRF token');
    }

    const body = await response.json();
    return body.token;
}

async function postToServer(items, token) {
    return fetch('/api/sync/batch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token,
        },
        body: JSON.stringify({ items }),
        credentials: 'same-origin',
    });
}

let syncInFlight = null;

/*
 * The "online" event, background sync and a fresh registration can all ask to sync at once;
 * run one sync at a time and let the others wait for it.
 */
function syncPending() {
    if (!syncInFlight) {
        syncInFlight = runSync().finally(() => {
            syncInFlight = null;
        });
    }

    return syncInFlight;
}

async function runSync() {
    if (currentUserId() === null) {
        return;
    }

    let items;
    try {
        items = await getPendingItems();
    } catch (error) {
        console.error('Failed to read outbox:', error);
        return;
    }

    if (items.length === 0) {
        updateOfflineBadge();
        return;
    }

    let token = getCsrfToken();
    let response;

    try {
        response = await postToServer(items, token);
    } catch (error) {
        // Network error - still offline. Will retry later.
        console.warn('Sync failed (offline):', error);
        return;
    }

    // Stale CSRF token - refresh it once and retry.
    if (response.status === 419) {
        try {
            token = await refreshCsrfToken();
            response = await postToServer(items, token);
        } catch (error) {
            console.warn('Sync failed after token refresh:', error);
            return;
        }
    }

    if (response.ok) {
        const body = await response.json().catch(() => ({}));
        const syncedIds = (body.synced_ids ?? []).filter((id) => id != null);
        if (syncedIds.length > 0) {
            await deleteOutboxItems(syncedIds);
            showToast(`Synced ${syncedIds.length} record(s) to the server.`);
        }

        // Rejected items stay queued and are retried; say so rather than failing silently.
        const errors = body.errors ?? [];
        if (errors.length > 0) {
            console.warn('Items rejected by server:', errors);
            showToast(`${errors.length} saved record(s) could not be synced: ${errors[0]}`, 'error');
        }
    } else {
        console.warn('Sync rejected by server:', response.status);
    }

    updateOfflineBadge();
}

function registerBackgroundSync() {
    if (!('serviceWorker' in navigator) || !('SyncManager' in window)) {
        return;
    }
    navigator.serviceWorker.ready
        .then((registration) => registration.sync.register('sync-outbox'))
        .catch((error) => console.warn('Background sync registration failed:', error));
}

/* ---------------------------------- UI ---------------------------------- */

function buildBanner() {
    const banner = document.createElement('div');
    banner.id = 'offline-banner';
    banner.style.cssText = [
        'position:fixed', 'top:0', 'left:0', 'right:0', 'z-index:9999',
        'background:#fff3cd', 'color:#664d03', 'padding:8px 16px',
        'text-align:center', 'font-size:13px', 'font-weight:600',
        'font-family:Inter,sans-serif', 'box-shadow:0 2px 6px rgba(0,0,0,0.1)',
        'display:none',
    ].join(';');
    banner.textContent = "You're offline. Registrations, visit logs, growth metrics and vaccine doses entered now are saved on this device and synced automatically.";
    document.body.prepend(banner);
}

function updateOfflineBanner() {
    const banner = document.getElementById('offline-banner');
    if (!banner) return;
    banner.style.display = navigator.onLine ? 'none' : 'block';
}

function updateOfflineBadge() {
    if (currentUserId() === null) {
        return;
    }

    countPendingItems()
        .then((count) => {
            let badge = document.getElementById('pending-sync-badge');
            if (count > 0) {
                if (!badge) {
                    badge = document.createElement('div');
                    badge.id = 'pending-sync-badge';
                    badge.style.cssText = [
                        'position:fixed', 'bottom:16px', 'left:16px', 'z-index:9998',
                        'background:#005eb8', 'color:#ffffff', 'padding:10px 16px',
                        'border-radius:999px', 'font-size:13px', 'font-weight:600',
                        'font-family:Inter,sans-serif', 'box-shadow:0 4px 12px rgba(0,0,0,0.2)',
                        'display:flex', 'align-items:center', 'gap:8px',
                    ].join(';');
                    badge.innerHTML = `<span>&#128260;</span> <span id="pending-sync-count"></span>`;
                    document.body.appendChild(badge);
                }
                document.getElementById('pending-sync-count').textContent =
                    `${count} record(s) waiting to sync`;
            } else if (badge) {
                badge.remove();
            }
        })
        .catch(() => {});
}

function toastStack() {
    let stack = document.getElementById('offline-toast-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'offline-toast-stack';
        stack.style.cssText = [
            'position:fixed', 'bottom:16px', 'right:16px', 'z-index:9999',
            'display:flex', 'flex-direction:column', 'align-items:flex-end', 'gap:8px',
        ].join(';');
        document.body.appendChild(stack);
    }
    return stack;
}

function showToast(message, kind = 'success') {
    const isError = kind === 'error';
    const toast = document.createElement('div');
    toast.setAttribute('role', isError ? 'alert' : 'status');
    toast.style.cssText = [
        `background:${isError ? '#ba1a1a' : '#146f00'}`, 'color:#ffffff', 'padding:12px 20px',
        'border-radius:10px', 'font-size:14px', 'font-weight:500', 'max-width:min(420px, calc(100vw - 32px))',
        'font-family:Inter,sans-serif', 'box-shadow:0 4px 12px rgba(0,0,0,0.25)',
        'transition:opacity 0.3s',
    ].join(';');
    toast.textContent = message;
    toastStack().appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, isError ? 8000 : 4000);
}

/* ---------------------------------- Registration form ---------------------------------- */

function setupRegistrationForm() {
    const form = document.getElementById('registrationForm');
    if (!form) return;

    form.addEventListener('submit', (event) => {
        if (navigator.onLine) {
            return; // Normal online submission.
        }

        event.preventDefault();

        queuePatientRegistration(new FormData(form))
            .then(() => {
                showToast('Registration saved on this device. It will sync when you reconnect.');
                form.reset();
                if (typeof goToStep === 'function') {
                    goToStep(1);
                }
            })
            .catch((error) => {
                console.error('Failed to queue offline registration:', error);
                showToast('Failed to save offline. Please try again.', 'error');
            });
    });
}

/* ---------------------------------- Clinical entry forms ---------------------------------- */

/*
 * Forms marked data-offline-type="<sync item type>" are queued instead of submitted while offline.
 * data-offline-context holds JSON merged into the item (e.g. which record it belongs to);
 * data-offline-label names the entry in messages; data-offline-once locks the form after queueing.
 * Patient pages are not cached, so this covers a page that was open when the connection dropped.
 */
function setupOfflineForms() {
    document.querySelectorAll('form[data-offline-type]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (navigator.onLine) {
                return;
            }

            event.preventDefault();

            const data = formDataToObject(new FormData(form));
            delete data._token;
            delete data._method;
            Object.assign(data, JSON.parse(form.dataset.offlineContext || '{}'), {
                // The server dates the entry from this moment, not from when it syncs.
                recorded_at: new Date().toISOString(),
            });

            const label = form.dataset.offlineLabel || 'Entry';

            queueItem(form.dataset.offlineType, data)
                .then(() => {
                    showToast(`${label} saved on this device. It will sync when you reconnect.`);
                    if (form.hasAttribute('data-offline-once')) {
                        form.querySelectorAll('button[type="submit"]').forEach((button) => {
                            button.disabled = true;
                            button.textContent = 'Queued';
                            button.title = 'Saved on this device; syncs when back online';
                            button.style.opacity = '0.6';
                        });
                    } else {
                        form.reset();
                        form.closest('[data-modal]')?.classList.add('hidden');
                    }
                })
                .catch((error) => {
                    console.error(`Failed to queue offline ${form.dataset.offlineType}:`, error);
                    showToast('Failed to save offline. Please try again.', 'error');
                });
        });
    });
}

/* ---------------------------------- Init ---------------------------------- */

function initOfflineSupport() {
    buildBanner();
    updateOfflineBanner();
    updateOfflineBadge();
    setupRegistrationForm();
    setupOfflineForms();

    // Entries can be left over from an earlier visit (browser closed while offline); send them now.
    if (navigator.onLine) {
        syncPending();
    }

    window.addEventListener('online', () => {
        updateOfflineBanner();
        syncPending();
    });

    window.addEventListener('offline', () => {
        updateOfflineBanner();
    });

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch((error) => {
                console.error('Service worker registration failed:', error);
            });
        });

        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'SYNC_NOW') {
                syncPending();
            }
        });
    }
}

initOfflineSupport();
