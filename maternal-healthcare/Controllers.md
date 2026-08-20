# Controllers Architecture

This application uses dedicated domain controllers adhering to the Single Responsibility Principle (SRP), supplemented by Form Requests, Model Policies, and Audit Logging.

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
| `update(Request, Patient)` | Enforces `PatientPolicy`, updates demographic & clinical attributes atomically |
| `patientPortal()` | Patient portal view with upcoming/overdue vaccines and unread messages |

## MaternalRecordController
**File:** `app/Http/Controllers/MaternalRecordController.php`

| Method | Purpose |
|---|---|
| `maternal(Request)` | Role-aware maternal tracking view, checkup visit history |
| `storeCheckup(Request)` | Logs new prenatal visit (fundal height, blood pressure, fetal heart rate, next appointment) |

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
| `send(Request, SmsService)` | Dispatches SMS via Capcom6 Android SMS Gateway |
| `updateSmsSettings(Request, SmsService)` | Configures gateway endpoint URL and authentication credentials |
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
| `reports()` | Monthly registration trends, vaccine compliance rates, reporting dashboards |
| `admin()` | Admin user management panel |

## SyncController
**File:** `app/Http/Controllers/SyncController.php`

| Method | Purpose |
|---|---|
| `token(Request)` | Returns fresh CSRF token for offline sync flow |
| `registrations(Request)` | Atomic batch synchronization endpoint supporting `patient_registration`, `maternal_checkup`, `child_growth`, and `immunization_update` |

## Related Pages
- [[Routes Map]]
- [[Entity Relationships]]
- [[Senior Review - Pros Cons and Roadmap]]
