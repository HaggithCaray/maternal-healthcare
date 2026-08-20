# Architecture

## High-Level Flow
```
Browser → Laravel App (php artisan serve :8000)
              ↓
         MySQL (XAMPP/Laragon :3306)
              
Browser ← WebSocket ← Reverb (php artisan reverb:start :8080)
```

## Application Layers

### Controllers
| Controller | Responsibility |
|-----------|----------------|
| `AuthController` | Login/logout, dashboard stats |
| `PageController` | All page views: records, register, edit, maternal, growth, immunization, SMS, messaging, reports, admin |
| `SyncController` | Offline sync API endpoint + CSRF token |

### Models (8 total)
See [[Entity Relationships]] for full diagram.

### Services
| Service | Purpose |
|---------|---------|
| `SmsService` | HTTP gateway: send SMS, check status, save settings |

### Middleware
| Middleware | Usage |
|-----------|-------|
| `auth` | Protects all routes except login |
| `role:admin` | Restricts admin panel |
| `CheckRole` | Custom: accepts variadic roles, aborts 403 |

### Events (Broadcasting)
| Event | Channel | Trigger |
|-------|---------|---------|
| `MessageSent` | `private conversation.{id}` | New chat message |
| `MessageRead` | `private conversation.{id}` | Messages marked read |

## Frontend Architecture
```
resources/
├── css/app.css          → Tailwind 4 + MD3 design tokens
├── js/
│   ├── app.js           → Entry (imports echo + offline)
│   ├── echo.js          → Laravel Echo + Reverb config
│   └── offline.js       → IndexedDB outbox + background sync
└── views/
    ├── layouts/         → app.blade (authenticated), guest.blade
    ├── auth/            → login.blade
    ├── patient/         → Patient-facing views (portal, edit, maternal, growth, immunization, messaging)
    └── *.blade          → Admin views (dashboard, records, register, maternal, growth, immunization, sms, messaging, reports, admin)
```

## Offline/PWA Flow
1. Service Worker intercepts navigation requests
2. `offline.js` queues registration data in IndexedDB `outbox`
3. On reconnect: background sync POSTs to `/api/sync/patients`
4. CSRF token refreshed on 419 responses

## Related Pages
- [[Routes Map]]
- [[Entity Relationships]]
- [[Local Development Setup]]
