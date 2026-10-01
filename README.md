# Maternal Health Hub

A maternal and child health monitoring system for **Barangay Bicao Health Station, Carmen, Bohol**.
Midwives register mothers and children, log prenatal visits, growth measurements and vaccines, and
message patients. Mothers get their own portal to follow their pregnancy and their children's health.

> Capstone project. The clinical checks in this system are a **screening aid** — the midwife's
> judgment always decides referral.

---

## Features

| Area | What it does |
|------|--------------|
| **Patient records** | Register maternal and child patients, search and filter records, edit details, link each child to the mother's record. |
| **Patient portal** | Mothers sign in to see their prenatal visits and their children's growth, vaccines and reminders. The midwife sees a temporary password once after registration; patients then set their own under **Change Password**. |
| **Prenatal care** | Gestational age and due date from the LMP; every visit is flagged for high blood pressure, abnormal fetal heart rate and post-term pregnancy; risk profile from age, parity, height and medical history; next visit date follows the prenatal schedule. |
| **Child growth** | WHO Child Growth Standards z-scores (weight-for-age, length/height-for-age, weight-for-length/height) with nutritional status: underweight, stunted, wasted, overweight. |
| **Immunization** | Philippine EPI schedule generated at registration (BCG, Hepatitis B, Pentavalent, OPV, IPV, PCV, MMR); midwives mark doses as given. |
| **Messaging** | Real-time chat between midwife and patients (Laravel Reverb). Attachments are private and only served to the two people in the conversation. |
| **SMS** | Send SMS through a Capcom6 Android SMS Gateway; gateway settings are stored encrypted. |
| **Reports** | Per-year registrations by month, immunization coverage, prenatal visits and high-risk counts, and a child nutrition summary. Printable. |
| **Offline (PWA)** | Installable web app with a service worker. New patient registrations made offline are queued on the device and synced when the connection returns; a retried sync never creates the same record twice. |
| **Admin** | Add healthcare worker accounts, reset passwords, deactivate or reactivate any login, and browse the activity log of every record viewed or changed. |
| **Security** | Role-based access (healthcare worker / patient), authorization policies, login throttling, deactivated accounts signed out immediately, and other devices signed out after a password change. Patient pages and attachments are never stored on the device, so a shared tablet shows nothing after logout. |

## Tech stack

- **Backend:** Laravel 13, PHP 8.3+ (developed on 8.4)
- **Database:** MySQL 8 (run through XAMPP)
- **Frontend:** Blade, Tailwind CSS, Vite
- **Real-time:** Laravel Reverb + Laravel Echo
- **Tests:** PHPUnit (feature tests run on an in-memory SQLite database)

---

## Setup (Windows + XAMPP)

### 1. Install the requirements

- **XAMPP** — for MySQL (and phpMyAdmin)
- **PHP 8.3 or newer** — check with `php -v`. In `php.ini`, make sure `extension=fileinfo`,
  `extension=zip`, `extension=pdo_mysql` and `extension=mbstring` are enabled.
- **Composer**
- **Node.js 20+** and npm
- **Git**

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

Chat messages and "typing..." appear live through **Laravel Reverb**, a WebSocket server that runs
next to the app. If this step is skipped, chat still works but new messages only show after a refresh.

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

### Demo accounts

Created by the seeder — change or remove them before real use.

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
npm run build
```

- `assessments:recalculate` re-runs the gestational-age, prenatal risk and WHO growth checks on
  records saved before those checks existed.
- `attachments:make-private` moves chat files uploaded before attachments became private out of the
  public folder. Both are safe to run more than once.

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
```

- Give the demo accounts (`health@example.com`, `patient@example.com`) real passwords from
  **Admin**, or deactivate them.
- After any `.env` change run `php artisan config:cache` (and `npm run build` if a `REVERB_*` value changed).
- Back up the MySQL database and `storage/app` (SMS settings and chat attachments) regularly.

## Running the tests

```bash
php artisan test
```

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
  Http/Controllers/   Patient, maternal, child health, chat, SMS, reports, offline sync
  Models/             Patient, MaternalRecord, MaternalCheckup, ChildRecord, GrowthMeasurement, ...
  Policies/           Who can view or change each record
  Services/           PrenatalAssessment, WhoGrowthStandards, SmsService
database/             Migrations and the demo seeder
resources/
  views/              Blade pages (partials/ holds shared cards and badges)
  data/who-growth/    WHO growth reference tables
  js/                 Echo (chat) and offline queue
public/               sw.js, manifest.json and offline.html for the PWA
routes/               web.php, api.php (offline sync), channels.php, console.php (artisan commands)
tests/Feature/        Feature tests
maternal-healthcare/  Developer notes (Obsidian vault)
documentation/        Capstone paper, diagrams and screenshots
```

## Sharing a demo online

`cloudflare_tunnel_setup.md` explains how to expose the local app with a temporary Cloudflare
tunnel. It is meant for demos, not production.

## Troubleshooting

| Problem | Fix |
|---|---|
| `No connection could be made because the target machine actively refused it` | MySQL is not running — start it in the XAMPP Control Panel. |
| Chat messages only appear after a refresh | Check `BROADCAST_CONNECTION=reverb` and the `REVERB_*` / `VITE_REVERB_*` lines in `.env` (see *Set up real-time chat*), run `npm run build`, and start `php artisan reverb:start`. The browser console says "Real-time chat is off" when the build has no Reverb settings. |
| Pages look unstyled | Run `npm run build`. |
| A newly registered patient cannot sign in | Open *Edit Patient* → **Reset Password** and give the patient the temporary password shown. |
| Someone cannot sign in although the password is right | Their account may be deactivated — check **Admin** → the user → **Reactivate**. |

## Known limitations

- Offline: the registration form must be opened once while signed in and online (its offline copy is
  deleted at logout). Prenatal visits, growth metrics and vaccine doses can be entered offline only on a
  patient page that was already open when the connection dropped; patient pages are not stored on the device.
- Real-time chat over the Cloudflare demo link only works on the computer running the app: other devices
  load the chat but try to reach Reverb on their own `localhost`, so they see new messages on refresh.
- There is no email-based password reset; staff reset passwords from the Admin page or *Edit Patient*.
- Developmental milestones are shown as WHO age windows, not recorded per child.
