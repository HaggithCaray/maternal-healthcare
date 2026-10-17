# Known Gaps

> Audit of what the system still lacks (as of the 274-test suite).
> See also [[Senior Review - Pros Cons and Roadmap]] for what was already fixed.

## Functional gaps

| # | Gap | Notes |
|---|-----|-------|
| ~~1~~ | ~~No patient/record deletion or archival~~ | **Resolved** — archive/restore hides patients from lists, search, dashboard counts and exports (admin, audit-logged, reversible). Permanent `DELETE` only accepts an archived patient, is refused while linked children exist, removes the linked login + chat history, and writes a final audit entry after the row is gone |
| 2 | No email password reset | Staff reset only (Admin / Edit Patient) |
| ~~3~~ | ~~No automated backups~~ | **Resolved** — `backup:run` writes one zip to `storage/app/backups` (dump, `storage/app`, `.env`), prunes after 30 days; scheduled 2:00 AM |
| 4 | Queue configured but unused | `QUEUE_CONNECTION=database`, nothing ever dispatches |
| ~~5~~ | ~~No automatic SMS reminders~~ | **Resolved** — `reminders:send` texts due vaccines and next prenatal/postpartum visits, scheduled 8:00 AM, off until ticked on the SMS page |
| ~~6~~ | ~~CSV/PDF export descoped~~ | **CSV resolved** — patient/visit/immunization/report exports on Records and Reports (admin, audit-logged); PDF is still browser-print only |
| 7 | Audit log never pruned | No retention command; grows forever |

## Engineering / ops gaps

| # | Gap | Notes |
|---|-----|-------|
| 8 | No CI | 274 tests, no `.github/workflows` |
| 9 | No production deployment story | XAMPP + artisan serve only; no supervisor/nginx/HTTPS doc |
| 10 | Only `UserFactory` | No factories for Patient/Child/Maternal models |
| ~~11~~ | ~~Doc drift~~ | **Resolved** — vault synced with the postpartum/export/reminder/backup work: test counts (252 / 1,129), new services, routes, schema and the backup/scheduler setup |
| ~~12~~ | ~~Scheduler not armed on the server~~ | **Resolved** — Windows task "Maternal Health Hub scheduler" created 2026-10-15: SYSTEM, every minute, test run exited 0. CLI one-liner documented in the README; GUI steps were already there |

## Suggested priority

1. Wire up delete/archive (#1)
2. GitHub Actions test runner (#8)

## Related Pages

- [[Senior Review - Pros Cons and Roadmap]]
- [[Milestones]]
- [[File Map]]
