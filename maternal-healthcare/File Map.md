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
│   ├── api.php          → offline sync (/api/sync/*)
│   └── console.php      → CLI commands + schedule (reminders 8:00 AM, backups 2:00 AM)
├── app/
│   ├── Http/Controllers/ → domain controllers (Auth, Patient, MaternalRecord,
│   │                       ChildHealth, SmsGateway, Chat, Report, Sync, Export)
│   ├── Models/           → database models (Patient, MaternalRecord, MaternalCheckup,
│   │                       PostpartumVisit, ChildRecord, Immunization, SmsMessage, …)
│   ├── Services/
│   │   ├── SmsService.php             → SMS gateway (cloud o local) + encrypted settings
│   │   ├── ReminderService.php        → automated SMS reminders
│   │   ├── BackupService.php          → daily backup zips
│   │   ├── PrenatalAssessment.php     → prenatal risk rules
│   │   ├── PostpartumAssessment.php   → postpartum schedule + risk rules
│   │   └── PatientRegistration.php    → registration shared by forms + offline sync
│   ├── Support/
│   │   └── CsvExporter.php            → streamed CSV downloads
│   ├── Events/           → broadcast events (MessageSent, MessageRead)
│   └── Http/Middleware/CheckRole.php → role check (admin/user)
├── database/
│   ├── migrations/       → table definitions (24 files)
│   └── seeders/          → sample data
├── resources/views/      → HTML templates (Blade)
│   ├── *.blade.php       → admin/health-worker screens
│   ├── partials/maternal/postpartum.blade.php → postpartum UI (admin modal + patient read-only)
│   └── patient/*.blade.php → patient portal screens
├── tests/                → 269 unit & feature tests
└── public/               → frontend assets (built via Vite)
```

## Key Files & Roles

| File | Trabaho |
|------|---------|
| `routes/web.php` | Gihubit ang URL (e.g. `/records`) ug asa nga controller ang modala |
| `routes/console.php` | Artisan commands + ang schedule: `reminders:send` 8:00 AM, `backup:run` 2:00 AM |
| `app/Http/Controllers/PatientController.php` | Records, registration, edit patient (apil ang delivery date) |
| `app/Http/Controllers/MaternalRecordController.php` | Prenatal checkups + postpartum visits |
| `app/Http/Controllers/ExportController.php` | CSV exports (records, prenatal, postpartum, immunizations, report summary) |
| `app/Http/Controllers/AuthController.php` | Login, logout, dashboard |
| `app/Models/Patient.php` | Representa sa `patients` table |
| `app/Models/PostpartumVisit.php` | Postpartum visits — nag-derive og days postpartum, risk status ug next visit |
| `app/Services/SmsService.php` | Nag-send og SMS via gateway (cloud o local), nag-format sa phone numbers |
| `app/Services/ReminderService.php` | Nag-text og due nga vaccines/visits; dedupe key para di ma-double |
| `app/Services/BackupService.php` | Naghimo og backup zip sa `storage/app/backups`, 30 days retention |
| `app/Services/PostpartumAssessment.php` | DOH 48h/1w/2w/6w schedule + BP risk flags (pareho sa prenatal) |
| `app/Support/CsvExporter.php` | Nag-stream og CSV nga may UTF-8 BOM + formula-escape |
| `app/Events/MessageSent.php` | Nag-broadcast og bag-ong message sa Reverb |
| `app/Events/MessageRead.php` | Nag-notify nga nabasa na ang message |
| `routes/channels.php` | Nag-allow sa mga user nga mo-subscribe sa conversation channels |
| `resources/js/app.js` | Frontend JS — nag-setup sa Echo (WebSockets) |
| `resources/js/offline.js` | IndexedDB outbox + background sync sa `/api/sync/batch` |

## Database Tables & Relationships

```text
users ──┬── patients (user_id)
        └── chat_messages (sender_id / receiver_id)

patients ──┬── maternal_records ──┬── maternal_checkups
           │                      └── postpartum_visits
           └── child_records ──┬── immunizations
                               └── growth_measurements

patients ── sms_messages
```

- `patients.user_id` → `users.id` (patient nga naay login account)
- `maternal_records.patient_id` → `patients.id` ; `maternal_records.delivery_date` → gigamit sa postpartum schedule
- `maternal_checkups.maternal_record_id` → `maternal_records.id`
- `postpartum_visits.maternal_record_id` → `maternal_records.id`
- `child_records.patient_id` → `patients.id` ; `child_records.mother_id` → `patients.id` (inahan)
- `immunizations.child_record_id` → `child_records.id`
- `growth_measurements.child_record_id` → `child_records.id`
- `sms_messages.patient_id` → `patients.id` ; `sms_messages.dedupe_key` → unique, usa ka reminder per dose/visit
- `chat_messages.sender_id` / `receiver_id` → `users.id`

## Related

- [[How It Works]]
- [[Project Overview]]
- [[PWA Offline Features]]
- [[Services]]
