# Progress Log

Dated entries of what changed in the project, what is verified, and what to do next. Newest first.

## 2026-10-15 — Scheduler armed, patient archive + hard delete

**Commits** (pushed to `main`): `5cd2c0c` chore: Windows scheduler task + README one-liner · `2bf4d8d` feat: archive/restore + guarded admin hard delete.
**Suite:** 269 tests / 1,196 assertions, all passing (was 252 / 1,129).
Earlier the same day: `979d43a` docs: full vault sync (14 files, closed Known Gaps #11).

### Done — Known Gaps #12 (scheduler)
- Windows task **Maternal Health Hub scheduler** exists on the clinic machine: runs as **SYSTEM**, daily 12:00 AM with 1-minute repetition, `C:\xampp\php\php.exe C:\maternal-health-care\artisan schedule:run` (absolute artisan path because `schtasks` has no working-directory option)
- Verified: manual run returned **Last Result 0**. A task created this way can only be queried from an elevated prompt — *Access is denied* otherwise (normal, not a fault)
- README now carries the one-line `schtasks` command beside the GUI steps; Known Gaps #12 closed

### Done — Known Gaps #1 (archive + hard delete)
- `PATCH /patients/{id}/archive` and `/restore` — admin-only, audit-logged, reversible. New status `Archived` (no migration: status is a plain varchar); the edit form round-trips it
- Archived patients are hidden from: Records list **and** search, records KPI cards, dashboard mother/child counts + 6-month registration trend + due-vaccine lists, Reports totals, the report-summary CSV and the masterlist CSV. The **Show archived** checkbox (`?archived=1`) brings them back everywhere; the export link carries the flag
- Records Quick Actions gained **Archive / Restore / Delete permanently**; archived rows render dimmed with a grey status pill. **Portal login is unaffected** (decided deliberately)
- `DELETE /patients/{id}` has three rails: the patient must be **archived first**, it is **refused while linked children exist** (archive the mother instead), and the browser prompt requires typing **DELETE** exactly
- The delete transaction removes the linked login **only when it belongs to that patient** (a child sharing the mother's login keeps it) — taking that account's chat history with it — then the patient row cascades maternal/child records, checkups, postpartum visits, growth, immunizations and SMS history
- A **final audit entry** (id, name, type, deleted login) is written *after* the row is gone; earlier audit rows survive because `audit_logs` holds no patient FK
- 17 tests in `tests/Feature/ArchiveAndDeleteTest.php`

### Verify next session (overnight proof the scheduler fires)
- After 2:00 AM: a fresh `storage/app/backups/backup-*.zip` produced by the task itself
- After 8:00 AM: a `reminders:send` line in `storage/logs/laravel.log`. It will say reminders are **disabled** — the Automated reminders switch is still OFF — which still proves the schedule fired
- If neither appears: run the task from an elevated prompt (`schtasks /run /tn "Maternal Health Hub scheduler"`) and check `php artisan schedule:list`

### Carry-over
- **SMS gateway username/password must be re-entered on `/sms`** — a test bug deleted `storage/app/sms_settings.json` (bug fixed: tests back it up and restore it now). Last seen URLs in the audit log: `http://10.49.240.142:8080` and `https://api.sms-gate.app/3rdparty/v1`
- Automated reminders stay **OFF** until the switch is ticked on the SMS page

### Decisions this session
| Decision | Choice |
|----------|--------|
| Scheduler run-as | SYSTEM — fires even when nobody is logged in |
| Deletion story | Archive **+** admin hard delete (two-step), not archive-only |
| Archived portal login | Keeps working — archiving only changes what staff see |

### Suggested next
**Known Gaps #7 (audit log never pruned)** — same shape as the backup/reminder schedule already in place (a retention command scheduled alongside them). Then #8 CI.

## 2026-10-15 (earlier) — Features 1–5 complete and vault synced

Already covered by the commits referenced above: CSV export (`18fb593`), SMS settings test fixes (`0544c17`), automated SMS reminders (`1108799`), automated backups (`2be5810`), and the full vault sync (`979d43a`). Suite moved 238 → 252 tests across that work. Details live in [[Milestones]] (Phase 8) and [[Known Gaps]].
