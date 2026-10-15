# Testing Guide

## Test Framework
- **Framework:** PHPUnit 12.5+ (via Laravel)
- **Test database:** SQLite in-memory (default)
- **Run command:** `php artisan test`
- **Status:** 269 tests, 1,196 assertions — all passing

## Test Files

### Overview
| File | Tests | Covers |
|------|-------|--------|
| `tests/Unit/ExampleTest.php` | 1 | Sanity check |
| `tests/Feature/AuthControllerTest.php` | 9 | Login, logout, dashboard statistics |
| `tests/Feature/PageControllerTest.php` | 28 | Records, registration, edit patient, maternal/growth/immunization pages, SMS send/settings/status, messaging, admin panel |
| `tests/Feature/SyncControllerTest.php` | 7 | Offline sync endpoints: token, batch, per-item errors |
| `tests/Feature/SyncAndRegistrationTest.php` | 26 | `PatientRegistration` service, offline patient/checkup/growth/vaccine sync incl. postpartum visits |
| `tests/Feature/DomainControllersTest.php` | 10 | Patient, maternal, child, SMS and report controllers |
| `tests/Feature/RecordsAndReportsTest.php` | 10 | Records filters and stats, reports page, maternal care figures |
| `tests/Feature/ClinicalAssessmentTest.php` | 19 | Gestational age derivation, prenatal risk flags, WHO z-scores |
| `tests/Feature/PostpartumAndExportTest.php` | 14 | Postpartum visits, DOH schedule, risk escalation + all CSV exports |
| `tests/Feature/RemindersTest.php` | 8 | Automated SMS reminders: off switch, dedupe, retry, visit windows |
| `tests/Feature/BackupTest.php` | 6 | Backup zip contents, replayable dump, pruning, command, go-live warning |
| `tests/Feature/ArchiveAndDeleteTest.php` | 17 | Archive/restore lifecycle, hidden-from-everywhere behavior, two-step permanent delete |
| `tests/Feature/AccessHardeningTest.php` | 15 | Staff-only SMS center, role gates, 403s |
| `tests/Feature/RateLimitAndValidationTest.php` | 11 | Rate limits and server-side validation |
| `tests/Feature/AccountManagementTest.php` | 16 | Admin user management, password changes |
| `tests/Feature/ChildAccountTest.php` | 11 | Child-linked accounts and portal rules |
| `tests/Feature/ChildContactTest.php` | 14 | Child contact details resolving to the mother |
| `tests/Feature/PrivacyAndFamilyTest.php` | 9 | Family links and privacy |
| `tests/Feature/SharedInboxTest.php` | 14 | Shared inbox and messaging flows |
| `tests/Feature/ChatRealtimeTest.php` | 3 | Reverb events and channel authorization |
| `tests/Feature/RealContentTest.php` | 9 | Seed content sanity |
| `tests/Feature/GoLiveTest.php` | 7 | `app:go-live-check` output |
| `tests/Feature/PhilippineTimeTest.php` | 4 | Asia/Manila dating rules |
| `tests/Feature/ExampleTest.php` | 1 | Route smoke test |
| **Total** | **269** | |

### The newest feature files
| File | Tests | What they prove |
|------|-------|-----------------|
| `ArchiveAndDeleteTest.php` | 17 | Archived patients are hidden from lists, search, dashboard counts, reports and the masterlist CSV (and back with `?archived=1`); deletion needs archive first, is refused while a mother has linked children, cascades clinical records + SMS + the linked login's chat history, writes a final audit entry, and the portal login keeps working while archived |
| `PostpartumAndExportTest.php` | 14 | 48h/1w/2w/6w schedule derivation from the delivery date, High Risk escalation, offline `postpartum_visit` sync, and every CSV export (filters honoured, UTF-8 BOM, formula-injection escaping, audit-log entry per export) |
| `RemindersTest.php` | 8 | Nothing is sent until the switch is ticked; a `Sent` reminder is never repeated; a `Failed` one retries without duplicating the row; today/tomorrow/overdue windows for prenatal and postpartum |
| `BackupTest.php` | 6 | The zip holds database + `.env` + `storage/app` but never nests itself; the SQLite dump replays into a working database (including `ALTER`-added columns); backups older than 30 days are pruned; command success **and** failure paths |

## Running Tests
```bash
# All tests
php artisan test

# Specific test
php artisan test --filter="test_admin_can_update_maternal_patient"

# Specific class
php artisan test --filter="PageControllerTest"

# Verbose output
php artisan test --filter="..." -v
```

> Tests touching the real SMS gateway settings file (`storage/app/sms_settings.json`) back it
> up first and restore it in a `finally` block — a test run must never lose the credentials an
> admin entered. Do not "clean up" that file with a bare `unlink()`.

## Related Pages
- [[Setup Guide]]
- [[Controllers]]
- [[Milestones]]
