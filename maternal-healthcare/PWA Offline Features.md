# PWA Offline Features

## Overview
Midwives can keep working when the connection drops. Patient registrations, prenatal visit logs, growth metrics and vaccine doses entered offline are saved on the device and synced automatically when the connection returns.

## What works offline

| Entry | Where | Works offline when |
|-------|-------|--------------------|
| Patient registration | `/register` | Always. The form is cached the first time it is opened online |
| Prenatal visit log | `/maternal?id=…` "New Visit Log" | The patient page was already open when the connection dropped |
| Growth metrics | `/growth?id=…` "Log New Metrics" | The patient page was already open when the connection dropped |
| Vaccine dose | `/immunization?id=…` "Mark Given" | The patient page was already open when the connection dropped |

For privacy, patient pages are never cached on the device, so a patient page that was not already open cannot be opened offline. The offline page explains this.

## Components

### Service Worker (`public/sw.js`)
- **Cache strategy:** stale-while-revalidate for built assets, icons and images; network-first for navigation
- **Cached pages:** only `/register`, in a cache that is deleted at logout and when the login page opens
- **Precaches:** `offline.html`, manifest, icons
- **Background sync:** on reconnect, tells open pages to sync (`SYNC_NOW`)

### IndexedDB Outbox (`resources/js/offline.js`)
- **Database:** `maternal-health-db`, object store `outbox`
- **Each item:** `uuid` (makes retries safe), `type`, `status`, `user_id` (who entered it), `created_at`, `data`
- **Clinical entries** also carry `recorded_at`, the moment the entry was made

### Marking a form for offline queuing
A form is queued instead of submitted while offline when it has `data-offline-type`:

| Attribute | Purpose |
|-----------|---------|
| `data-offline-type` | Sync item type: `maternal_checkup`, `child_growth`, `immunization_update` |
| `data-offline-context` | JSON merged into the item, e.g. `{"maternal_record_id": 12}` |
| `data-offline-label` | Name used in messages, e.g. "Prenatal visit" |
| `data-offline-once` | Lock the form after queuing (the "Mark Given" button becomes "Queued") |

Modal forms close and reset after queuing (the modal needs `data-modal`). Field limits in the forms match the server rules, so entries are checked before they are queued.

## Sync Flow
1. Entry made while offline → queued in IndexedDB, toast + "N record(s) waiting to sync" badge
2. Sync runs when the connection returns (`online` event), when Background Sync fires, and on every page load while online
3. Only entries made by the signed-in staff member are sent, so on a shared tablet a visit is never recorded under the next midwife who signs in. Patients never sync
4. `POST /api/sync/batch`; on a 419 the CSRF token is refreshed once and the request retried
5. Synced items are removed from the device. Rejected items stay queued and are retried; the server returns a reason for each (`rejected: [{id, type, reason}]`), which is kept on the entry
6. The badge turns red ("N need attention") when an entry was refused. Tapping it opens **Saved on this device**: every queued entry with what it is, when it was saved and why it was refused, plus **Try again now** and **Discard** (after confirming; the entry is lost)

## API Sync Endpoint
```
POST /api/sync/batch   (staff only)
```
**Payload:**
```json
{
    "items": [
        { "id": 1, "uuid": "…", "type": "maternal_checkup",
          "data": { "maternal_record_id": 12, "weight_kg": "62.4", "bp": "118/76", "recorded_at": "2026-10-01T05:46:18.370Z" } }
    ]
}
```
**Response:**
```json
{
    "success": true,
    "synced": 1,
    "synced_ids": [1],
    "duplicates": 0,
    "errors": []
}
```

### Server rules
- Each item is applied at most once (keyed by `uuid`), so a retry after a lost response does not duplicate it
- Visit, measurement and dose dates come from `recorded_at`, converted to the app timezone; entries dated in the future are rejected
- Attendant / administered-by is the signed-in staff member
- A vaccine dose already marked Given (online, or from another device) is left as recorded
- Every applied item gets the same audit log entry as the online form, with `"source": "offline_sync"`

## UI Indicators
- **Offline banner:** yellow banner at the top while disconnected
- **Pending badge:** count of this user's queued entries
- **Toasts:** green when an entry is queued or synced, red when the server rejects an entry

## Known limits
- Offline is detected with `navigator.onLine`. Wi-Fi with no internet can look "online", in which case the normal submit fails and the offline page is shown

## Related Pages
- [[Architecture]]
- [[Setup Guide]]
- [[Milestones]]
