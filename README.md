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
| **Patient portal** | Mothers sign in to see their prenatal visits and their children's growth and vaccines. The midwife sees a temporary password once after registration and can reset it from *Edit Patient*. |
| **Prenatal care** | Gestational age and due date from the LMP; every visit is flagged for high blood pressure, abnormal fetal heart rate and post-term pregnancy; risk profile from age, parity, height and medical history; next visit date follows the prenatal schedule. |
| **Child growth** | WHO Child Growth Standards z-scores (weight-for-age, length/height-for-age, weight-for-length/height) with nutritional status: underweight, stunted, wasted, overweight. |
| **Immunization** | Philippine EPI schedule generated at registration (BCG, Hepatitis B, Pentavalent, OPV, IPV, PCV, MMR); midwives mark doses as given. |
| **Messaging** | Real-time chat between midwife and patients (Laravel Reverb). Attachments are private and only served to the two people in the conversation. |
| **SMS** | Send SMS through a Capcom6 Android SMS Gateway; gateway settings are stored encrypted. |
| **Reports** | Per-year registrations by month, immunization coverage, prenatal visits and high-risk counts, and a child nutrition summary. Printable. |
| **Offline (PWA)** | Installable web app with a service worker. New patient registrations made offline are queued on the device and synced when the connection returns. |
| **Security** | Role-based access (healthcare worker / patient), authorization policies, login throttling and an audit log of record access and changes. |

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
(`root`, no password, database `healthcare_db`) and a local Reverb server.

### 4. Create the database

Start **MySQL** in the XAMPP Control Panel, open phpMyAdmin (`http://localhost/phpmyadmin`) and run:

```sql
CREATE DATABASE healthcare_db;
```

Then create the tables and demo data:

```bash
php artisan migrate --seed
```

### 5. Build the frontend

```bash
npm run build
```

### 6. Run the app (two terminals)

```bash
php artisan serve
```

```bash
php artisan reverb:start
```

Open **http://127.0.0.1:8000**.

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
| Sending a chat message fails, or messages only appear after a refresh | Start `php artisan reverb:start`. If you changed any `REVERB_*` value, run `npm run build` again. To run without real-time chat, set `BROADCAST_CONNECTION=log`. |
| Pages look unstyled | Run `npm run build`. |
| A newly registered patient cannot sign in | Open *Edit Patient* → **Reset Password** and give the patient the temporary password shown. |

## Known limitations

- Only patient registration works offline; prenatal visits, growth and vaccine updates need a connection.
- Patients cannot change their own password yet.
- Some widgets are still static sample content: *Dev. Milestones* and *Reminders* on the growth page,
  *To-Do This Week* on the maternal page.

## Repositories

| Repository | Scope |
|---|---|
| [maternal-healthcare](https://github.com/HaggithCaray/maternal-healthcare) | This repository — the full system |
| [M-healthcare](https://github.com/HaggithCaray/M-healthcare) | Base version: authentication, dashboard, patient records and portal |
