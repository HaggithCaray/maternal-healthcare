# Milestones

## Milestone: 10 Percent — Core Registration & Records
- [x] Patient registration (maternal + child)
- [x] Patient records list with search/filter
- [x] Role-based access control
- [x] Database seeding with sample data
- [x] Unit and feature tests

## Milestone: Edit Patient
- [x] Edit patient form (personal info + maternal/child records)
- [x] Quick Actions dropdown in records table
- [x] DB::transaction for atomic updates
- [x] EDD auto-compute from LMP
- [x] Medical history checkbox handling
- [x] Feature tests for view + update

## Milestone: Health Tracking
- [x] Maternal checkup tracking
- [x] Child growth monitoring
- [x] Immunization schedule (14 doses)
- [x] Growth chart visualization

## Milestone: Communication
- [x] SMS gateway integration
- [x] Real-time chat (Reverb WebSocket)
- [x] File attachments in chat
- [x] Online presence tracking

## Milestone: Offline & PWA
- [x] Service Worker (`public/sw.js`)
- [x] IndexedDB outbox (`resources/js/offline.js`)
- [x] Background sync
- [x] Offline patient registration
- [x] Multi-entity offline batch sync (`/api/sync/batch`) for checkups, growth, and vaccines

## Milestone: Analytics & Reporting
- [x] Dashboard KPIs
- [x] Reports & analytics with database-agnostic monthly aggregations
- [x] Vaccine compliance tracking

## Milestone: Security Hardening & Pentest Remediation
- [x] Removed hardcoded default credentials (`Hash::make('password')`) in patient creation and sync flows
- [x] Implemented cryptographically secure randomized credentials (`Str::random(16)`)
- [x] Built and registered Laravel Model Policies (`PatientPolicy`, `MaternalRecordPolicy`, `ChildRecordPolicy`, `ImmunizationPolicy`, `ChatMessagePolicy`)
- [x] Fortified chat file upload pipeline (MIME whitelisting, UUID hashing, 25MB file size limit)
- [x] Implemented PHI/PII Audit Logging engine (`audit_logs` table and logging hooks)

## Milestone: Domain Architecture Refactoring
- [x] Deconstructed 730+ line `PageController` God Controller into focused domain controllers:
  - `PatientController`
  - `MaternalRecordController`
  - `ChildHealthController`
  - `SmsGatewayController`
  - `ChatController`
  - `ReportController`
- [x] Extracted 9 reusable Form Request validation classes (`app/Http/Requests/`)
- [x] Refactored [routes/web.php](file:///c:/maternal-health-care/routes/web.php) to bind directly to domain controllers
- [x] Comprehensive test suite expanded and 100% passing (47 tests, 227 assertions)

---

## Completed Phases
- [x] **Phase 1**: Core Registration & Records
- [x] **Phase 2**: Edit Patient Functionality
- [x] **Phase 3**: Architecture Update (Docker → XAMPP/Laragon)
- [x] **Phase 4**: Security Hardening, Policies & Pentest Remediation
- [x] **Phase 5**: Domain Controller & Form Request Architectural Refactoring
- [x] **Phase 6**: PHI Compliance & Audit Logging Engine

## In Progress
- Phase 7: Multi-Entity Offline Form Auto-Queuing UI
- Phase 8: Data Export (PDF/CSV) for DOH Reports

## Upcoming
- Phase 9: Multi-Barangay Support & Federation
- Phase 10: Biometric / Smartcard Field Authentication

---

## Related Pages
- [[Project Overview]]
- [[Senior Review - Pros Cons and Roadmap]]
- [[Controllers]]
- [[Routes Map]]
- [[Testing Guide]]
