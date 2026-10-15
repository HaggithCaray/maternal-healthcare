# Controllers Architecture

This application uses dedicated domain controllers adhering to the Single Responsibility Principle (SRP), supplemented by Model Policies, rate limits and Audit Logging. Input is validated inside each controller action; registration, Edit Patient and offline sync share `PatientRegistration::rules()`.

## AuthController
**File:** `app/Http/Controllers/AuthController.php`

| Method | Purpose |
|---|---|
| `showLoginForm()` | Renders `auth.login` view |
| `login(Request)` | Validates credentials, authenticates, redirects by role |
| `logout(Request)` | Invalidates session, regenerates CSRF token, redirects to login |
| `dashboard()` | Counts mothers, children, today's scheduled vaccines, unread messages; renders `dashboard` |

## PatientController
**File:** `app/Http/Controllers/PatientController.php`

| Method | Purpose |
|---|---|
| `records(Request)` | Patient list with search/filter (name, phone, type, status), calculates KPI statistics, logs audit trail |
| `register(Request)` | Displays registration form (GET) or delegates to store (POST) |
| `store(Request)` | Creates Patient + User (with secure randomized credentials) + MaternalRecord/ChildRecord in atomic `DB::transaction` |
| `edit(Patient)` | Enforces `PatientPolicy`, loads relations, renders `patient.edit` view |
| `update(Request, Patient)` | Enforces `PatientPolicy`, updates demographic & clinical attributes atomically; validates and saves the maternal `delivery_date` |
| `patientPortal()` | Patient portal view with upcoming/overdue vaccines and unread messages |

## MaternalRecordController
**File:** `app/Http/Controllers/MaternalRecordController.php`

| Method | Purpose |
|---|---|
| `maternal(Request)` | Role-aware maternal tracking view: checkup history plus the postpartum section (postpartum visits and the DOH 48h/1w/2w/6w schedule derived from the delivery date) |
| `storeCheckup(Request)` | Logs new prenatal visit (fundal height, blood pressure, fetal heart rate, next appointment) |
| `storePostpartum(Request)` | Logs a postpartum visit on the same `POST /maternal` route, distinguished by `visit_type=postpartum`; risk flags, days postpartum and next visit are derived on save |

## ChildHealthController
**File:** `app/Http/Controllers/ChildHealthController.php`

| Method | Purpose |
|---|---|
| `growth(Request)` | Role-aware child growth view with growth chart logs |
| `storeGrowth(Request)` | Logs child weight, height, and age milestones |
| `immunization(Request)` | Displays 14-dose EPI immunization schedule |
| `updateImmunizationStatus(Request)` | Marks scheduled vaccine dose as administered by midwife |

## SmsGatewayController
**File:** `app/Http/Controllers/SmsGatewayController.php`

| Method | Purpose |
|---|---|
| `sms(Request, SmsService)` | SMS dispatch center and outbound audit history |
| `send(Request, SmsService)` | Dispatches SMS via the configured gateway (cloud or local) |
| `updateSmsSettings(Request, SmsService)` | Configures gateway URL, credentials and the `reminders_enabled` switch (the unchecked checkbox reads as off); audit-logged |
| `testSmsGatewayConnection(SmsService)` | Live gateway reachability test |

## ChatController
**File:** `app/Http/Controllers/ChatController.php`

| Method | Purpose |
|---|---|
| `messaging(Request)` | Two-way WebSocket chat between midwives and patients, marks unread messages, handles secured attachments (whitelisted MIME, UUID storage) |

## ReportController
**File:** `app/Http/Controllers/ReportController.php`

| Method | Purpose |
|---|---|
| `reports()` | Monthly registration trends, vaccine compliance rates, maternal care figures (postpartum visit count and latest activity) |
| `admin()` | Admin user management panel |

## ExportController
**File:** `app/Http/Controllers/ExportController.php`

Streamed CSV downloads for MHO reporting; every export writes an audit-log entry, and the
clinical logs are `role:admin` (`viewAny`). Built on `App\Support\CsvExporter` (UTF-8 BOM,
formula-injection escaping, lazy queries).

| Method | Purpose |
|---|---|
| `patients(Request)` | Patient masterlist CSV on `/records/export`, honouring the Records page filters |
| `prenatalVisits()` | Prenatal visit log CSV |
| `postpartumVisits()` | Postpartum visit log CSV |
| `immunizations()` | Immunization (dose) log CSV |
| `reportSummary()` | Annual report summary CSV (key metrics one per row) |

## SyncController
**File:** `app/Http/Controllers/SyncController.php`

| Method | Purpose |
|---|---|
| `token(Request)` | Returns fresh CSRF token for offline sync flow |
| `registrations(Request)` | Atomic batch synchronization endpoint supporting `patient_registration`, `maternal_checkup`, `child_growth`, `immunization_update` and `postpartum_visit` |

## Related Pages
- [[Routes Map]]
- [[Entity Relationships]]
- [[Services]]
- [[Senior Review - Pros Cons and Roadmap]]
