# Maternal Health Hub

A maternal and child health monitoring system for **Barangay Bicao Health Station, Carmen, Bohol**.
Healthcare workers register mothers and children, log prenatal and postpartum visits, growth
measurements and vaccines, and message patients. Mothers get their own portal to follow their
pregnancy and their children's health.

> Capstone project. The clinical checks in this system are a **screening aid** — the midwife's
> judgment always decides referral.

---

## Features

| Area | What it does |
|------|--------------|
| **Patient records** | Register mothers and children, search and filter records, edit details. **Archive** a patient to take them off the lists, dashboard, reports and exports (reversible); a **permanent delete** only accepts an archived patient and also removes their login, chat history and attachment files. Every change is audit-logged. |
| **Registration** | A guided form that asks *"Who are you registering?"* first and only shows what applies. A child is linked to the mother's record and uses her current address, phone and emergency contact; a child without a registered mother takes a parent or guardian's details. |
| **Patient portal** | Mothers sign in to see their prenatal and postpartum visits and their children's growth, vaccines and reminders. Children have no login of their own: the mother sees them in her portal. Staff hand out a temporary password once; patients then set their own under **Change Password**. |
| **Prenatal care** | Gestational age and due date from the LMP; every visit is flagged for high blood pressure, abnormal fetal heart rate and post-term pregnancy; risk profile from age, parity, height and medical history; next visit date follows the prenatal schedule. |
| **Postpartum care** | Once a delivery date is recorded, the four routine postnatal checks (48 hours, 1, 2 and 6 weeks) show as Done / Due / Overdue, and each visit is flagged for postpartum high or low blood pressure. |
| **Child growth** | WHO Child Growth Standards z-scores (weight-for-age, length/height-for-age, weight-for-length/height) with nutritional status: underweight, stunted, wasted, overweight. |
| **Immunization** | Philippine EPI schedule generated at registration (BCG, Hepatitis B, Pentavalent, OPV, IPV, PCV, MMR); staff mark doses as given. Allergies are shown on every clinical page. |
| **Messaging** | One shared health-station inbox: every healthcare worker sees and answers every patient's conversation, and the patient sees replies from any of them. Live updates, "typing...", read receipts and unread badges (Laravel Reverb). Attachments are private: only the patient and staff can open them. |
| **SMS** | Send SMS through a Capcom6 SMS Gateway (cloud server or the phone's local address). Optional **automated reminders** text mothers once a day about vaccine doses and prenatal/postpartum visits that are due. Gateway settings are stored encrypted and the password is never shown on the page. |
| **Reports and exports** | Per-year registrations, immunization coverage, prenatal and postpartum visits, high-risk counts and a child nutrition summary. CSV exports for the Municipal Health Office: patient masterlist, prenatal and postpartum visit logs, immunization log and the yearly summary. |
| **Offline (PWA)** | Installable web app. Registrations, visit logs, growth measurements and vaccine doses entered without a connection are saved on the device and sent when it returns; a retried sync never creates a record twice. Entries the server refuses are listed under **Saved on this device** with the reason, to retry or discard. |
| **Backups** | Daily password-protected backup of the database, files and settings, kept 30 days, optionally copied to a USB drive or synced folder. The Admin page warns when backups stop. |
| **Admin** | Add healthcare worker accounts, reset passwords, deactivate or reactivate logins, and browse the activity log of every record viewed or changed. |
| **Security** | Role-based access (healthcare worker / patient) with authorization policies; rate limits on sign-in, password changes, chat and SMS; deactivated accounts and archived patients are signed out at once; other devices are signed out after a password change. Patient pages and attachments are never stored on the device, so a shared tablet shows nothing after logout. |

## Tech stack

- **Backend:** Laravel 13, PHP 8.3+ (developed on 8.4)
- **Database:** MySQL 8 (run through XAMPP)
- **Frontend:** Blade, Tailwind CSS, Vite
- **Real-time:** Laravel Reverb + Laravel Echo
- **Tests:** PHPUnit feature tests on an in-memory SQLite database

---

## Setup (Windows + XAMPP)

### 1. Install the requirements

- **XAMPP** — for MySQL (and phpMyAdmin). Install MySQL as a Windows service so it starts with
  Windows: XAMPP Control Panel (run as Administrator) → the ✖ next to MySQL → *Yes*. The app,
  backups and reminders all stop while MySQL is down.
- **PHP 8.3 or newer** — check with `php -v`. In `php.ini` (`C:\xampp\php\php.ini`):
  - enable `extension=fileinfo`, `extension=zip`, `extension=pdo_mysql` and `extension=mbstring`;
  - raise the upload limits so chat attachments up to 25 MB arrive:
    `upload_max_filesize=26M` and `post_max_size=28M`. XAMPP ships 2M / 8M, which turns away
    most phone photos.
- **Composer**
- **Node.js 20+** and npm
- **Git**
- **7-Zip** — to open the password-protected backups

### 2. Get the code and install dependencies

```bash
git clone https://github.com/HaggithCaray/maternal-healthcare.git
cd maternal-healthcare
composer install
npm install
```

### 3. Configure the environment

```bash
copy .env.example .env
php artisan key:generate
```

(`cp .env.example .env` on Git Bash.) The example file is already set up for XAMPP's MySQL
(`root`, no password, database `healthcare_db`) and a local Reverb server. Optionally set
`CLINIC_PHONE` to show the health station's number to patients.

### 4. Create the database

Start **MySQL** in the XAMPP Control Panel, open phpMyAdmin (`http://localhost/phpmyadmin`) and run:

```sql
CREATE DATABASE healthcare_db;
```

Then create the tables and demo data:

```bash
php artisan migrate --seed
```

### 5. Set up real-time chat (Reverb)

Chat messages, "typing..." and unread badges update live through **Laravel Reverb**, a WebSocket
server that runs next to the app. If this step is skipped, chat still works but new messages only
show after a refresh.

1. Make sure `.env` has these lines. They come from `.env.example`; an `.env` copied from an older
   version or another project often lacks them, which is the most common reason live chat doesn't work:

   ```env
   BROADCAST_CONNECTION=reverb

   REVERB_APP_ID=100001
   REVERB_APP_KEY=local-reverb-key
   REVERB_APP_SECRET=local-reverb-secret
   REVERB_HOST=localhost
   REVERB_PORT=8080
   REVERB_SCHEME=http

   VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
   VITE_REVERB_HOST="${REVERB_HOST}"
   VITE_REVERB_PORT="${REVERB_PORT}"
   VITE_REVERB_SCHEME="${REVERB_SCHEME}"
   ```

   `BROADCAST_CONNECTION=log` turns live chat off; messages are then only written to the log.
   Before real use, replace the key and secret with random values, e.g. from
   `php -r "echo bin2hex(random_bytes(16));"`.
2. **Build the frontend after any change to a `REVERB_*` value** (next step). The browser gets these
   settings only when the frontend is built.
3. Start Reverb in its own terminal and keep it open (step 7).

To check it works: open the chat as a healthcare worker in one browser and as a patient in another
(e.g. Chrome and Edge). A message sent in one appears in the other without a refresh, and the
browser console has no "Real-time chat is off" warning. If port 8080 is taken, set both
`REVERB_PORT` and `REVERB_SERVER_PORT` to another port and build again.

### 6. Build the frontend

```bash
npm run build
```

### 7. Run the app (two terminals)

```bash
php artisan serve
```

```bash
php artisan reverb:start
```

Open **http://127.0.0.1:8000**. Keep both terminals open while using the app; without the Reverb
terminal, chat falls back to showing new messages on refresh.

### 8. Set up the scheduled jobs

Backups and SMS reminders need Windows Task Scheduler — see [Scheduled jobs](#scheduled-jobs-reminders-and-backups).

### Demo accounts

Created by the seeder — give them real passwords or deactivate them before real use.

| Role (choose on the login page) | Email | Password |
|---|---|---|
| Healthcare Worker | `health@example.com` | `password` |
| Patient | `patient@example.com` | `password` |

---

## After pulling new changes

```bash
composer install
php artisan migrate
php artisan assessments:recalculate
php artisan attachments:make-private
npm install
npm run build
php artisan config:clear
```

- `assessments:recalculate` re-runs the gestational-age, prenatal risk and WHO growth checks on
  records saved before those checks existed.
- `attachments:make-private` moves chat files uploaded before attachments became private out of the
  public folder. Both are safe to run more than once.
- `config:clear` picks up new settings; use `config:cache` instead on a live server.

## Scheduled jobs (reminders and backups)

The schedule lives in `routes/console.php` (`php artisan schedule:list` shows it), but nothing
runs it by itself on Windows. Two jobs rely on it:

| Command | Runs | What it does |
| --- | --- | --- |
| `reminders:send` | once a day, at the first check after 8:00 AM | Texts mothers about vaccine doses due within two days and prenatal/postpartum visits due today, tomorrow or missed in the last 14 days. Archived patients are never texted, and prenatal reminders stop once a delivery date is recorded. Each reminder is sent once. Nothing is sent until **Automated reminders** is ticked on the SMS page. |
| `backup:run` | once a day, at the first check after 2:00 AM | One `backup-<date time>.zip` in `storage/app/backups` (see [Backups](#backups)). Backups older than 30 days are deleted. |

Either keep a console running:

```bash
php artisan schedule:work
```

or, so it also runs after a reboot, let Windows Task Scheduler wake it every minute:

1. Open **Task Scheduler** → **Create Task…** (not "Basic Task").
2. **General**: name it `Maternal Health Hub scheduler`.
3. **Triggers**: *New…* → *Daily* → 12:00 AM → tick *Repeat task every:* **1 minute** →
   *For a duration of:* **Indefinitely**.
4. **Actions**: *New…*
   - Program/script: `C:\xampp\php\php.exe`
   - Add arguments: `artisan schedule:run`
   - Start in: the project folder (the one containing `artisan`)
5. **Conditions**: untick *Start the task only if the computer is on AC power*.

`schedule:run` executes whatever is due that minute and exits, so a task every minute is the
normal way to drive Laravel's scheduler without cron.

Both jobs are *checked* every 15 minutes rather than pinned to one exact minute, because a
clinic PC is regularly asleep or switched off at 2:00 AM and 8:00 AM — and a missed minute
would otherwise be missed for the whole day. Each job instead runs once, at the first
opportunity after its time: reminders from 8:00 AM (a once-a-day marker stops a second run
that day), backups from 2:00 AM (a backup already written that day stops it). When the PC is
on all night, they run at the intended times.

**Fast path (one command instead of the dialog)** — run in an *elevated* prompt, replacing the
artisan path with yours:

```bat
schtasks /create /tn "Maternal Health Hub scheduler" /tr "C:\xampp\php\php.exe C:\maternal-health-care\artisan schedule:run" /sc daily /st 00:00 /ri 1 /du 9999:59 /ru SYSTEM /rl highest /f
```

Notes:
- The task runs as **SYSTEM**, so it fires even when nobody is logged in.
- **MySQL must be running.** Laravel keeps its scheduler state in the database cache, so while
  MySQL is down every run fails (`Last Result: 1` and `No connection could be made` in
  `storage/logs/laravel.log`) and neither job runs. Install MySQL as a Windows service (setup
  step 1) so this can't happen after a restart.
- Force a run with `schtasks /run /tn "Maternal Health Hub scheduler"`; check
  `schtasks /query /tn "Maternal Health Hub scheduler" /fo list /v` — `Last Result: 0` means
  it executed cleanly. A task created this way can only be inspected from an elevated prompt
  (otherwise *Access is denied*).

### Backups

```bash
php artisan backup:run
```

- Each zip holds `database.sql.gz`, `.env` and everything under `storage/app` (chat attachments,
  SMS settings) except the backups folder: every patient record and the app key. Protect it in
  `.env`:
  - `BACKUP_PASSWORD=<long password>` encrypts every file in the zip (AES-256). Open the zips
    with **7-Zip**; Windows' built-in zip cannot read them. Keep the password somewhere safe away
    from this PC: without it the backups cannot be restored.
  - `BACKUP_COPY_PATH=E:\backups` (a USB drive or a synced cloud folder) copies each zip there
    too, so a dead disk does not take the backups with it. Copies are pruned after 30 days; if
    the drive is unplugged the backup still succeeds and the failed copy is logged.
- The dump uses **mysqldump**, autodetected from `PATH` and then
  `C:\xampp\mysql\bin\mysqldump.exe`. For any other install, set
  `MYSQLDUMP_BINARY=C:\path\to\mysqldump.exe`.
- After changing these settings run `php artisan config:clear` (or `config:cache` on a live server).
- **To restore:** open the zip with 7-Zip (with the password), extract `database.sql.gz`, run
  `gunzip database.sql.gz` and `mysql -u root healthcare_db < database.sql`, then put `.env`
  and `storage/app` back.

The **Admin** page shows the last backup and turns red when none is less than two days old.

## Before using it with real patients

The setup above is for development. Before real patient data goes in, run:

```bash
php artisan app:go-live-check
```

It lists what still needs changing, marks what blocks real use (**FIX FIRST**) and says how to fix
each item. The usual changes in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true   # only once the app is served over HTTPS
REVERB_APP_KEY=<random>      # new key and secret, then npm run build
REVERB_APP_SECRET=<random>
BACKUP_PASSWORD=<long password, kept safe off this PC>
BACKUP_COPY_PATH=<USB drive or synced folder>
```

- Give the demo accounts (`health@example.com`, `patient@example.com`) real passwords from
  **Admin**, or deactivate them.
- Raise PHP's upload limits (setup step 1) and install MySQL as a Windows service.
- Set up the scheduled jobs so backups and reminders keep running.
- After any `.env` change run `php artisan config:cache` (and `npm run build` if a `REVERB_*`
  value changed).

## Running the tests

```bash
php artisan test
```

The tests use an in-memory SQLite database and their own backups folder, so they never touch
real records or backups. Expected errors (e.g. Reverb down) are not written to `laravel.log`.

---

## Clinical rules used

**Prenatal visits** (`app/Services/PrenatalAssessment.php`)

| Check | Result |
|---|---|
| Blood pressure ≥ 160/110 | High Risk — refer urgently |
| Blood pressure ≥ 140/90 | High Risk — pre-eclampsia screen after 20 weeks, possible chronic hypertension before |
| Blood pressure < 90/60 | Monitor |
| Fetal heart rate < 110 or > 160 bpm | High Risk |
| 42 weeks or more | High Risk — post-term |
| Next visit | every 4 weeks until 28 weeks, every 2 weeks until 36, then weekly |

A High Risk visit also marks the patient **High Risk** in Records; only staff lower it again.
The risk profile also lists maternal age under 18 or 35 and over, four or more previous births,
height under 145 cm, conditions from the medical-history checklist and a missing LMP.

**Postpartum visits** (`app/Services/PostpartumAssessment.php`)

| Check | Result |
|---|---|
| Blood pressure ≥ 160/110 | High Risk — refer urgently for possible postpartum pre-eclampsia |
| Blood pressure ≥ 140/90 | High Risk — assess for postpartum pre-eclampsia |
| Blood pressure < 90/60 | Monitor — dizziness, bleeding or shock |
| Schedule | within 48 hours, 1 week, 2 weeks and 6 weeks after delivery |

**Child growth** (`app/Services/WhoGrowthStandards.php`) — children 0–5 years, WHO cut-offs:

| Indicator | < −3 SD | < −2 SD | > +2 SD | > +3 SD |
|---|---|---|---|---|
| Weight-for-age | Severely underweight | Underweight | Overweight | Overweight |
| Length/height-for-age | Severely stunted | Stunted | Normal | Tall |
| Weight-for-length/height | Severely wasted | Wasted | Overweight | Obese |

Values outside WHO's plausible range are marked **Recheck Measurement**. The reference tables are
the official WHO LMS files in `resources/data/who-growth/` (source and license in that folder's README).

---

## Project structure

```text
app/
  Http/Controllers/   Patients, maternal and child health, chat, SMS, reports and CSV exports,
                      admin, own account, offline sync
  Models/             Patient, MaternalRecord, MaternalCheckup, PostpartumVisit, ChildRecord,
                      GrowthMeasurement, Immunization, ChatMessage, SmsMessage, AuditLog, ...
  Policies/           Who can view or change each record
  Services/           PatientRegistration, PrenatalAssessment, PostpartumAssessment,
                      WhoGrowthStandards, SmsService, ReminderService, BackupService
  Support/            CsvExporter
config/backup.php     Backup folder, password and second copy
database/             Migrations and the demo seeder
resources/
  views/              Blade pages (partials/ holds shared cards and badges)
  data/who-growth/    WHO growth reference tables
  js/                 Echo (chat), live inbox badges, offline queue
public/               sw.js, manifest.json and offline.html for the PWA
routes/               web.php, api.php (offline sync), channels.php,
                      console.php (artisan commands and the schedule)
tests/Feature/        Feature tests
maternal-healthcare/  Developer notes (Obsidian vault): progress log, deployment notes, testing guide
documentation/        Capstone paper, diagrams and screenshots
```

## Sharing a demo online

`cloudflare_tunnel_setup.md` explains how to expose the local app with a temporary Cloudflare
tunnel. It is meant for demos, not production.

## Troubleshooting

| Problem | Fix |
|---|---|
| `No connection could be made because the target machine actively refused it` | MySQL is not running — start it in the XAMPP Control Panel, and install it as a Windows service so it starts with Windows. |
| The Admin page says no backup in days | MySQL was down or the scheduled task is missing. Start MySQL, check the task (see *Scheduled jobs*), then run `php artisan backup:run`. |
| Chat messages only appear after a refresh | Check `BROADCAST_CONNECTION=reverb` and the `REVERB_*` / `VITE_REVERB_*` lines in `.env` (see *Set up real-time chat*), run `npm run build`, and start `php artisan reverb:start`. The browser console says "Real-time chat is off" when the build has no Reverb settings. |
| Sending a photo in chat says the file is too large, or shows "Page Expired" | PHP's upload limit is too low. Set `upload_max_filesize=26M` and `post_max_size=28M` in `php.ini`, then restart `php artisan serve` (or Apache). |
| Pages look unstyled | Run `npm run build`. |
| A newly registered mother cannot sign in | A login is only created when an email is entered. Open *Edit Patient* → **Reset Password** (or **Create Portal Account**) and give her the temporary password shown. Children have no login. |
| Someone cannot sign in although the password is right | The account may be deactivated (**Admin** → the user → **Reactivate**), or the patient is archived (**Records** → show archived → **Restore**). |
| A backup zip won't open | It is password-protected: open it with 7-Zip and the `BACKUP_PASSWORD` from `.env`. |
| A saved offline entry keeps failing to sync | Tap the red sync badge: **Saved on this device** shows why the server refused it. Enter it again on the patient's page if needed, then **Discard** the saved copy. |

## Known limitations

- Offline: the registration form must be opened once while signed in and online (its offline copy is
  deleted at logout). Prenatal visits, growth measurements and vaccine doses can be entered offline
  only on a patient page that was already open when the connection dropped; patient pages are not
  stored on the device.
- Real-time chat over the Cloudflare demo link only works on the computer running the app: other devices
  load the chat but try to reach Reverb on their own `localhost`, so they see new messages on refresh.
- There is no email-based password reset; staff reset passwords from the Admin page or *Edit Patient*.
- Developmental milestones are shown as WHO age windows, not recorded per child.
- One health station only (Barangay Bicao); multi-barangay support is out of scope.
