# Known Gaps

> Audit of what the system still lacks (as of the 224-test suite, commit 67e78bd).
> See also [[Senior Review - Pros Cons and Roadmap]] for what was already fixed.

## Functional gaps

| # | Gap | Notes |
|---|-----|-------|
| 1 | No patient/record deletion or archival | Policy `delete()` methods exist but no controller/route uses them |
| 2 | No email password reset | Staff reset only (Admin / Edit Patient) |
| 3 | No automated backups | README says "back up regularly" but no artisan command or scheduler |
| 4 | Queue configured but unused | `QUEUE_CONNECTION=database`, nothing ever dispatches |
| 5 | No automatic SMS reminders | Next-visit dates and due vaccines exist but reminders are manual |
| 6 | CSV/PDF export descoped | Reports are browser-print only |
| 7 | Audit log never pruned | No retention command; grows forever |

## Engineering / ops gaps

| # | Gap | Notes |
|---|-----|-------|
| 8 | No CI | 224 tests, no `.github/workflows` |
| 9 | No production deployment story | XAMPP + artisan serve only; no supervisor/nginx/HTTPS doc |
| 10 | Only `UserFactory` | No factories for Patient/Child/Maternal models |
| 11 | Doc drift | Vault notes still say "47 tests, 227 assertions" |

## Suggested priority

1. Wire up delete/archive (#1)
2. GitHub Actions test runner (#8)
3. Scheduler + visit/vaccine reminder SMS (#3, #5)
4. Refresh stale test counts in [[Senior Review - Pros Cons and Roadmap]] and [[Milestones]] (#11)

## Related Pages

- [[Senior Review - Pros Cons and Roadmap]]
- [[Milestones]]
- [[File Map]]
