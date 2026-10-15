# Services

## SmsService
**File:** `app/Services/SmsService.php`

Handles communication with an external SMS gateway via HTTP API. Two modes are detected from
the saved URL: the public **cloud** server (host ends in `sms-gate.app`, API under
`/3rdparty/v1`, reachable from any network) and the phone's **local** server (only reachable
on the same Wi-Fi/Ethernet).

### Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `getSettings()` | `(): array` | Reads config from `storage/app/sms_settings.json` (decrypted) or `.env` fallback |
| `saveSettings()` | `(string $url, string $username, string $password, bool $remindersEnabled = false): bool` | Persists gateway config + reminders switch, encrypted at rest |
| `remindersEnabled()` | `(): bool` | Whether the admin switched on automated reminders (off by default) |
| `checkStatus()` | `(): bool` | Cloud: lists the account's devices (no unauthenticated health endpoint there); local: GET `{url}/health` |
| `sendSms()` | `(string $to, string $message): array` | POST with basic auth — cloud `{base}/messages` (plural), local `{base}/message` (singular); HTTP 202 counts as success |
| `formatPhoneNumber()` | `(string $phone): string` | Converts PH local formats to E.164 (+639171234567) |

### Phone Number Formats
| Input | Output |
|-------|--------|
| `09171234567` | `+639171234567` |
| `9171234567` | `+639171234567` |
| `639171234567` | `+639171234567` |
| `+639171234567` | `+639171234567` (unchanged) |

### Gateway Settings Storage
`storage/app/sms_settings.json` is **encrypted at rest** (Laravel `Crypt`) and holds `url`,
`username`, `password` and `reminders_enabled`. The URL is stored without a trailing slash;
for the cloud host the `/3rdparty/v1` suffix is appended automatically when missing. A
plaintext legacy file is re-encrypted the first time it is read.

### SMS Send Payload
```json
{
    "textMessage": "Your message here",
    "phoneNumbers": ["+639171234567"]
}
```

## ReminderService
**File:** `app/Services/ReminderService.php` — runs as `reminders:send` (scheduled 8:00 AM)

Texts mothers about what is actually due, once per thing:

| Reminder | Window | Dedupe key |
|----------|--------|-----------|
| Vaccine dose not yet given | scheduled today or within 2 days | `vaccine:{immunization id}` |
| Next prenatal checkup | today, tomorrow or overdue | `nextvisit:{checkup id}` |
| Next postpartum visit | today, tomorrow or overdue | `postpartum:{visit id}` |

- Only the **newest** prenatal/postpartum visit per mother counts — an older one's visit has since happened.
- A child's reminder goes to its mother's number (child contact fields resolve to hers).
- Each key is stored on `sms_messages.dedupe_key` (unique): a `Sent` row is never repeated, a
  `Failed` row is retried on the next run by updating the same row.
- Nothing sends unless **Automated reminders** is ticked on the SMS page; when off the command
  logs that and exits without messaging anyone.

## BackupService
**File:** `app/Services/BackupService.php` — runs as `backup:run` (scheduled 2:00 AM)

One dated `backup-Y-m-d_His.zip` in `storage/app/backups` holding:

- `database.sql.gz` — gzipped `mysqldump` on MySQL (password via `MYSQL_PWD`, output streamed
  straight to gzip; binary autodetected from `MYSQLDUMP_BINARY` → PATH →
  `C:\xampp\mysql\bin\mysqldump.exe`). On SQLite the schema and rows are written out as SQL
  instead, because `VACUUM INTO` refuses to run inside a transaction.
- `.env`
- everything under `storage/app` **except** the backups folder itself, so a backup never
  nests the older backups (or itself)

Backups older than 30 days are pruned after each run. Verified against the real XAMPP MariaDB
(20 tables dumped; the SQLite dump replays into a working database under test).

## PostpartumAssessment
**File:** `app/Services/PostpartumAssessment.php`

Postpartum care rules, mirroring `PrenatalAssessment` on the prenatal side:

- **DOH/WHO schedule** derived from the delivery date: 48 hours, 1 week, 2 weeks, 6 weeks
- **BP thresholds** identical to prenatal care: ≥140/90 flagged, ≥160/110 severe (urgent referral)
- Visit flags roll up to a status; a High Risk flag escalates the patient's status so she
  stands out in Records (only staff lower it again)

## CsvExporter
**File:** `app/Support/CsvExporter.php`

Streams a CSV download straight from a lazy query or generator, so large exports are never
held in memory:

- UTF-8 BOM, so Excel opens Filipino names and accents correctly
- cells starting with `=`, `+`, `-` or `@` are prefixed with `'` (CSV formula injection)
- rows may be an array or a closure returning one; the closure only runs when streaming
  starts, which keeps the underlying query lazy

## Related Pages
- [[Controllers]]
- [[Routes Map]]
- [[Milestones]]
