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

// Record why the server refused items (by outbox id); they stay queued and are retried.
function markItemsRejected(rejected) {
    return openDb().then((db) =>
        new Promise((resolve, reject) => {
            const tx = db.transaction(OUTBOX_STORE, 'readwrite');
            const store = tx.objectStore(OUTBOX_STORE);
            rejected.forEach(({ id, reason }) => {
                const request = store.get(id);
                request.onsuccess = () => {
                    if (request.result) {
                        store.put({
                            ...request.result,
                            last_error: reason,
                            failed_attempts: (request.result.failed_attempts ?? 0) + 1,
                            last_attempt_at: new Date().toISOString(),
                        });
                    }
                };
            });
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

function queueItem(type, data, label = null) {
    return addOutboxItem({
        uuid: generateUuid(),
        type,
        label,
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
    const data = formDataToObject(formData);
    const name = [data.first_name, data.last_name].filter(Boolean).join(' ');

    return queueItem('patient_registration', data, name ? `Registration of ${name}` : null);
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

        // Rejected items stay queued and are retried; keep the reason and say so rather than failing silently.
        const rejected = (body.rejected ?? []).filter((item) => item.id != null);
        if (rejected.length > 0) {
            console.warn('Items rejected by server:', rejected);
            await markItemsRejected(rejected).catch(() => {});
            showToast(`${rejected.length} saved record(s) could not be synced. Tap the sync badge to see why.`, 'error');
        }
        refreshSyncPanel();
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

    getPendingItems()
        .then((items) => {
            let badge = document.getElementById('pending-sync-badge');
            if (items.length === 0) {
                badge?.remove();
                closeSyncPanel();
                return;
            }

            if (!badge) {
                badge = document.createElement('button');
                badge.type = 'button';
                badge.id = 'pending-sync-badge';
                badge.title = 'See the entries saved on this device';
                badge.addEventListener('click', openSyncPanel);
                badge.innerHTML = '<span aria-hidden="true">&#128260;</span> <span id="pending-sync-count"></span>';
                document.body.appendChild(badge);
            }

            // Red when the server refused something: someone needs to look at it.
            const failing = items.filter((item) => item.last_error).length;
            badge.style.cssText = [
                'position:fixed', 'bottom:16px', 'left:16px', 'z-index:9998', 'border:none', 'cursor:pointer',
                `background:${failing ? '#ba1a1a' : '#005eb8'}`, 'color:#ffffff', 'padding:10px 16px',
                'border-radius:999px', 'font-size:13px', 'font-weight:600',
                'font-family:Inter,sans-serif', 'box-shadow:0 4px 12px rgba(0,0,0,0.2)',
                'display:flex', 'align-items:center', 'gap:8px',
            ].join(';');
            document.getElementById('pending-sync-count').textContent =
                `${items.length} record(s) waiting to sync${failing ? ` · ${failing} need attention` : ''}`;
        })
        .catch(() => {});
}

/* ---------------------------------- Saved entries panel ---------------------------------- */

const TYPE_LABELS = {
    patient_registration: 'Patient registration',
    maternal_checkup: 'Prenatal visit',
    child_growth: 'Growth measurement',
    immunization_update: 'Vaccine dose',
};

function element(tag, styles = [], text = null) {
    const el = document.createElement(tag);
    el.style.cssText = styles.join(';');
    if (text !== null) {
        el.textContent = text;
    }
    return el;
}

function panelButton(text, primary) {
    const button = element('button', [
        'border:none', 'border-radius:8px', 'padding:8px 14px', 'font-size:13px', 'font-weight:600', 'cursor:pointer',
        primary ? 'background:#005eb8;color:#ffffff' : 'background:#f1f3f8;color:#3f4a5a',
    ], text);
    button.type = 'button';
    return button;
}

function closeSyncPanel() {
    document.getElementById('sync-panel')?.remove();
}

function refreshSyncPanel() {
    if (document.getElementById('sync-panel')) {
        openSyncPanel();
    }
}

/*
 * Entries saved on this device that haven't reached the server: what they are, when they were made
 * and, if the server refused one, why. A refused entry can be discarded once it's been re-entered.
 */
async function openSyncPanel() {
    const items = await getPendingItems().catch(() => []);
    closeSyncPanel();
    if (items.length === 0) {
        return;
    }

    const overlay = element('div', [
        'position:fixed', 'inset:0', 'z-index:10000', 'background:rgba(0,0,0,0.45)',
        'display:flex', 'align-items:center', 'justify-content:center', 'padding:16px',
    ]);
    overlay.id = 'sync-panel';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Entries saved on this device');
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeSyncPanel();
        }
    });

    const panel = element('div', [
        'background:#ffffff', 'border-radius:12px', 'width:100%', 'max-width:520px', 'max-height:80vh', 'overflow:auto',
        'padding:20px', 'font-family:Inter,sans-serif', 'color:#1a1c20', 'box-shadow:0 12px 32px rgba(0,0,0,0.25)',
    ]);

    const header = element('div', ['display:flex', 'justify-content:space-between', 'align-items:center', 'gap:12px']);
    header.append(element('h2', ['font-size:18px', 'font-weight:700', 'margin:0'], 'Saved on this device'));
    const close = panelButton('Close', false);
    close.addEventListener('click', closeSyncPanel);
    header.append(close);
    panel.append(header);
    panel.append(element('p', ['font-size:13px', 'color:#5a6372', 'margin:6px 0 14px'],
        'These entries have not reached the server yet. They are sent automatically when the connection is back.'));

    items.forEach((item) => {
        const row = element('div', [
            'border:1px solid ' + (item.last_error ? '#f2b8b5' : '#e1e5ec'), 'border-radius:10px', 'padding:12px',
            'margin-bottom:10px', item.last_error ? 'background:#fff4f3' : 'background:#f8fafd',
        ]);
        row.append(element('p', ['font-weight:600', 'font-size:14px', 'margin:0'], item.label || TYPE_LABELS[item.type] || item.type));
        row.append(element('p', ['font-size:12px', 'color:#5a6372', 'margin:2px 0 0'],
            `${TYPE_LABELS[item.type] ?? 'Entry'} · saved ${new Date(item.created_at).toLocaleString()}`));

        if (item.last_error) {
            row.append(element('p', ['font-size:13px', 'color:#ba1a1a', 'margin:8px 0 0'], `Not accepted: ${item.last_error}`));
            row.append(element('p', ['font-size:12px', 'color:#5a6372', 'margin:4px 0 0'],
                "It will keep being retried. If it can't be fixed, enter it again on the patient's page and discard this copy."));
        }

        const discard = panelButton('Discard', false);
        discard.style.marginTop = '10px';
        discard.addEventListener('click', async () => {
            if (!window.confirm('Discard this entry? It has not reached the server and will be lost.')) {
                return;
            }
            await deleteOutboxItems([item.id]).catch(() => {});
            updateOfflineBadge();
            refreshSyncPanel();
        });
        row.append(discard);
        panel.append(row);
    });

    const retry = panelButton(navigator.onLine ? 'Try again now' : 'Offline: will retry when connected', true);
    retry.disabled = !navigator.onLine;
    retry.style.width = '100%';
    retry.addEventListener('click', () => {
        retry.disabled = true;
        retry.textContent = 'Sending...';
        syncPending().finally(refreshSyncPanel);
    });
    panel.append(retry);

    overlay.append(panel);
    document.body.append(overlay);
    close.focus();
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeSyncPanel();
    }
});

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

            queueItem(form.dataset.offlineType, data, form.dataset.offlineLabel || null)
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
