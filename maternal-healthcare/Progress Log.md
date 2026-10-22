# Progress Log

Dated entries of what changed in the project, what is verified, and what to do next. Newest first.

## 2026-10-23 — Audit: reminders, backups, dependencies, uploads

**Suite:** 309 tests / 1,361 assertions, all passing (was 288 / 1,271).

### Found and fixed
- **Backups had stopped Oct 16–23.** The scheduler task fired, but XAMPP's MySQL was down, so
  every `schedule:run` failed. Confirmed when MySQL came back: a backup was written at 07:30.
  The Admin page now shows the last backup and warns after two days.
- **Reminders would have texted the wrong people** (switched off, none sent): archived patients,
  mothers who had already delivered, and visits missed long ago (e.g. 2024). Now skipped; missed
  visits are texted only within 14 days.
- **Backups were plain zips** holding every record and `.env`. `BACKUP_PASSWORD` (AES-256) and
  `BACKUP_COPY_PATH` (second copy) added; the go-live check requires the password.
- **Dependencies:** Laravel 13.16 → 13.38, Symfony HTTP Foundation (unauthenticated ReDoS),
  Guzzle; axios removed. `composer audit` and `npm audit` both clean.
- **SMS gateway password** was in the SMS page source; now never sent to the page.
- **Permanent delete** left the patient's chat attachment files on disk; now removed.
- **Chat uploads over 2 MB failed**: PHP here allows only 2 MB (`upload_max_filesize`). The chat
  now uses the real limit and says so; README and go-live check say how to raise it.
- Smaller: chat pop-ups set as plain text, checkup notes capped at 5,000 characters, a growth
  entry on a non-child record is refused, unused `/storage/{path}` routes turned off.

### Still open
- **Archived patients can still sign in** (decided deliberately 2026-10-15). A fix that blocks
  sign-in and hides them from the staff chat list is parked in `git stash`, pending a decision.
- **On the machine:** install MySQL as a Windows service; set `BACKUP_PASSWORD` and
  `BACKUP_COPY_PATH`; raise PHP's upload limits (`upload_max_filesize=26M`,
  `post_max_size=28M`) and restart; before real use, `APP_DEBUG=false` and real passwords
  for the demo accounts.

## 2026-10-17 — Scheduled jobs catch up after sleep; scheduler fully diagnosed

**Suite:** 274 tests / 1,226 assertions, all passing (was 269 / 1,196).

### The overnight check failed — and why
The two verifications owed from 2026-10-15 never appeared: no backup zip after 2:00 AM on
either night, no `reminders:send` line at 8:00 AM. Diagnosis (elevated `schtasks /query /v`
plus laravel.log forensics):

- The Windows task **was firing every minute while the PC was awake** — the trigger was never
  the problem. But the clinic PC is asleep (or off) at both 2:00 AM and 8:00 AM: app usage
  shows it came on around 13:00 on Oct 16 and around 09:05 on Oct 17.
- Laravel's `dailyAt()` has **no catch-up** — a missed minute is missed for the whole day —
  so on this machine neither job could ever fire. A real design gap, not a setup mistake.
- Side finding: the task's `Last Result: 1` was MySQL, not the scheduler. Laravel checks its
  `illuminate:schedule:paused` flag in the cache — the **database** cache — at the start of
  every `schedule:run`, so with XAMPP's MySQL down (it was, from ~20:00 Oct 16 until 09:07
  Oct 17) every run crashed with `No connection could be made`. The app itself was unusable
  in that window too. MySQL does not auto-start with Windows — worth fixing separately.

### The fix — quarter-hour checks with catch-up guards (`routes/console.php`)
Both entries are now `everyFifteenMinutes()` behind a `when()` guard, so each job runs
**once at the first opportunity after its time**:
- **reminders:send** from 08:00 — the command sets a `reminders-run:<date>` cache marker
  after every completed run, including "switched off" (one log line a day, not one per
  check). A crashed run leaves the marker unset, so the next quarter-hour retries.
- **backup:run** from 02:00 — a backup zip dated today is the marker, so no second piece of
  state. PC first wakes at 13:15 → backup at ~13:15.
- Reminder dedupe keys already made runs idempotent, so a once-a-day run at whatever hour
  the machine comes on is safe by construction. PC on all night → runs at 02:00/08:00 as
  intended.

### Also done on the machine
- Task Scheduler **Operational event log enabled** (`wevtutil sl
  Microsoft-Windows-TaskScheduler/Operational /e:true`) — the task's run history is now
  recordable (was disabled: zero history).
- Task state confirmed 09:30 Oct 17: Status Ready, Enabled, SYSTEM, repeat every 1 min,
  duration until ~March 2027, Next Run within the minute.
- 5 new tests: `tests/Feature/ScheduleCatchUpTest.php` — cron expression, before/after both
  cut-off times, once-a-day marker (incl. yesterday-doesn't-block-today), zip-as-marker.
- README "Scheduled jobs" rewritten: catch-up behaviour, and a Notes line that MySQL must be
  up for `schedule:run` (the cache-backed pause flag).

### Still open
- Real-world proof arrives the first time the PC sleeps past a due time: expect a fresh
  `storage/app/backups/backup-*.zip` and **one** `reminders:send skipped…` line per day in
  `storage/logs/laravel.log`.
- Carry-overs from Oct 15 stand: **SMS gateway username/password must be re-entered on
  `/sms`**; Automated reminders switch still OFF.
- Operational, unfixed: XAMPP's MySQL does not start with Windows (twice now the app came
  up with the database down). Registering MySQL as a service would close this.
- Wake-to-run / no-sleep power settings were deliberately **not** changed (user decision).

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
