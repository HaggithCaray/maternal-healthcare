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

function getPendingItems() {
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

function countPendingItems() {
    return openDb().then((db) =>
        new Promise((resolve, reject) => {
            const request = outboxStore(db, 'readonly').index('status').count(IDBKeyRange.only('pending'));
            request.onsuccess = () => {
                const count = request.result;
                db.close();
                resolve(count);
            };
            request.onerror = () => {
                db.close();
                reject(request.error);
            };
        })
    );
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

function queuePatientRegistration(formData) {
    const data = formDataToObject(formData);

    return addOutboxItem({
        type: 'patient_registration',
        status: 'pending',
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
    return fetch('/api/sync/patients', {
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

async function syncPending() {
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
    banner.textContent = "You're offline. New registrations will be saved on this device and synced automatically.";
    document.body.prepend(banner);
}

function updateOfflineBanner() {
    const banner = document.getElementById('offline-banner');
    if (!banner) return;
    banner.style.display = navigator.onLine ? 'none' : 'block';
}

function updateOfflineBadge() {
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

function showToast(message) {
    const toast = document.createElement('div');
    toast.style.cssText = [
        'position:fixed', 'bottom:16px', 'right:16px', 'z-index:9999',
        'background:#146f00', 'color:#ffffff', 'padding:12px 20px',
        'border-radius:10px', 'font-size:14px', 'font-weight:500',
        'font-family:Inter,sans-serif', 'box-shadow:0 4px 12px rgba(0,0,0,0.25)',
        'transition:opacity 0.3s',
    ].join(';');
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
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
                showToast('Failed to save offline. Please try again.');
            });
    });
}

/* ---------------------------------- Init ---------------------------------- */

function initOfflineSupport() {
    buildBanner();
    updateOfflineBanner();
    updateOfflineBadge();
    setupRegistrationForm();

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
