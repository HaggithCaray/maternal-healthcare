# PWA Offline Features

## Overview
The app supports offline patient registration with automatic background sync when connectivity returns.

## Components

### Service Worker (`public/sw.js`)
- **Cache strategy:** Cache-first for static assets, network-first for navigation
- **Precaches:** offline.html, icons, static assets
- **Background sync:** Integrates with SyncManager API

### IndexedDB Outbox (`resources/js/offline.js`)
- **Database:** `maternal-health-db`
- **Object store:** `outbox`
- **Stores:** Serialized registration form data

### PWA Manifest (`public/manifest.json`)
```json
{
    "name": "Maternal Health Hub",
    "short_name": "MaternalHealth",
    "display": "standalone",
    "orientation": "portrait",
    "theme_color": "#005eb8",
    "start_url": "/portal"
}
```

## Offline Registration Flow
1. Patient opens registration form while online → form loads normally
2. Internet drops → user fills form, submits
3. `offline.js` intercepts submission → queues data in IndexedDB
4. Shows offline banner + pending sync badge
5. Internet returns → `syncPending()` POSTs to `/api/sync/patients`
6. CSRF token refreshed on 419 responses
7. Background sync via Service Worker SyncManager (when supported)

## API Sync Endpoint
```
POST /api/sync/patients
```
**Payload:**
```json
{
    "items": [
        {
            "type": "patient_registration",
            "data": { ... registration fields ... }
        }
    ]
}
```
**Response:**
```json
{
    "synced": [1, 2, 3],
    "errors": [
        { "item": {...}, "error": "Validation failed" }
    ]
}
```

## UI Indicators
- **Offline banner:** Red banner at top when disconnected
- **Pending badge:** Shows count of queued items
- **Toast notifications:** Success/error feedback on sync

## Related Pages
- [[Architecture]]
- [[Setup Guide]]
