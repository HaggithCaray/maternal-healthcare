---
tags:
  - project
  - reference
created: 2026-08-06
---

# File Map

Lista sa mga importanteng files sa project ug unsa ang ilang trabaho. Gamita ni kung gusto nimo mahibalo kung asa nga file ang usa ka feature.

## Folder Structure

```text
maternal-health-care/
├── routes/
│   ├── web.php          → URL routes (browsing)
│   ├── channels.php     → WebSocket channels (messaging)
│   └── console.php      → CLI commands
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php  → login/logout/dashboard
│   │   └── PageController.php  → mga pages (records, register, sms, messaging, etc.)
│   ├── Models/           → database models
│   ├── Services/
│   │   └── SmsService.php → SMS gateway integration
│   ├── Events/           → broadcast events (MessageSent, MessageRead)
│   └── Http/Middleware/CheckRole.php → role check (admin/user)
├── database/
│   ├── migrations/       → table definitions
│   └── seeders/          → sample data
├── resources/views/      → HTML templates (Blade)
│   ├── *.blade.php       → admin/health-worker screens
│   └── patient/*.blade.php → patient portal screens
├── tests/                → unit & feature tests
└── public/               → frontend assets (built via Vite)
```

## Key Files & Roles

| File | Trabaho |
|------|---------|
| `routes/web.php` | Gihubit ang URL (e.g. `/records`) ug asa nga controller ang modala |
| `app/Http/Controllers/AuthController.php` | Login, logout, dashboard |
| `app/Http/Controllers/PageController.php` | Core: records, registration, immunization, sms, messaging, growth, maternal, reports |
| `app/Models/Patient.php` | Representa sa `patients` table |
| `app/Services/SmsService.php` | Nag-send og SMS via Capcom6 gateway, nag-format sa phone numbers |
| `app/Events/MessageSent.php` | Nag-broadcast og bag-ong message sa Reverb |
| `app/Events/MessageRead.php` | Nag-notify nga nabasa na ang message |
| `routes/channels.php` | Nag-allow sa mga user nga mo-subscribe sa conversation channels |
| `resources/js/app.js` | Frontend JS — nag-setup sa Echo (WebSockets) |

## Database Tables & Relationships

```text
users ──┬── patients (user_id)
        └── chat_messages (sender_id / receiver_id)

patients ──┬── maternal_records ── maternal_checkups
           └── child_records ──┬── immunizations
                               └── growth_measurements

patients ── sms_messages
```

- `patients.user_id` → `users.id` (patient nga naay login account)
- `maternal_records.patient_id` → `patients.id`
- `maternal_checkups.maternal_record_id` → `maternal_records.id`
- `child_records.patient_id` → `patients.id` ; `child_records.mother_id` → `patients.id` (inahan)
- `immunizations.child_record_id` → `child_records.id`
- `growth_measurements.child_record_id` → `child_records.id`
- `sms_messages.patient_id` → `patients.id`
- `chat_messages.sender_id` / `receiver_id` → `users.id`

## Related

- [[How It Works]]
- [[Project Overview]]
- [[PWA Offline Features]]
