# Architecture

## High-Level Flow
```
Browser → Laravel App (php artisan serve :8000)
              ↓
         MySQL (XAMPP :3306)
              
Browser ← WebSocket ← Reverb (php artisan reverb:start :8080)
```

## Application Layers

### Controllers
| Controller | Responsibility |
|-----------|----------------|
| `AuthController` | Login/logout, dashboard stats |
| `PatientController` | Records, registration, edit patient |
| `MaternalRecordController` | Prenatal checkups + postpartum visits |
| `ChildHealthController` | Growth measurements, immunization tracking |
| `SmsGatewayController` | SMS center, gateway settings |
| `ChatController` | Messaging and attachments |
| `ReportController` | Reports and maternal care figures |
| `ExportController` | Streamed CSV exports (audit-logged; clinical logs admin-only) |
| `SyncController` | Offline sync API endpoint + CSRF token |

### Models (12 total)
See [[Entity Relationships]] for full diagram.

### Services
| Service | Purpose |
|---------|---------|
| `SmsService` | HTTP gateway: send SMS, check status, encrypted settings + reminders switch |
| `ReminderService` | Daily SMS reminders for due vaccines and visits (`reminders:send`, 8:00 AM) |
| `BackupService` | Daily backup zips (`backup:run`, 2:00 AM), 30-day retention |
| `PrenatalAssessment` / `PostpartumAssessment` | Gestational age, risk flags, DOH visit schedules |
| `PatientRegistration` | Registration rules shared by forms and offline sync |

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
