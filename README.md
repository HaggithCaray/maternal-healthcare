# Maternal and Child Health Monitoring System - HANDOFF DOCUMENT


## WHAT YOU CURRENTLY HAVE (THE FINISHED PARTS - ~90% COMPLETE)
We have successfully built the core web application, which is highly advanced and feature-rich. If you have an active internet connection, the system works perfectly and includes:
- **Database:** All tables for Patients, Records, Immunizations, and SMS are created.
- **Web Interface:** All the screens (Dashboard, Registration, Records, Reports) are fully built using Laravel Blade and Tailwind CSS.
- **Real-Time WebSockets:** Full-duplex real-time messaging is implemented using Laravel Reverb.
- **File Attachments:** Support for uploading massive 100MB image, video, and document attachments in the chat.
- **SMS Gateway Integration:** Live connection testing, character segment counting, and direct integration with the Capcom6 Android SMS Gateway.
- **Patient Portal & Branding:** Fully mobile-responsive views for patients, customized with DOH and municipal branding/watermarks.
- **Testing:** Comprehensive Unit and Feature tests for controllers and authentication.

## TECH STACK
- **Backend:** Laravel 13.x (PHP 8.4)
- **Frontend:** Blade + Tailwind CSS v4 + Vite
- **Database:** MySQL 8.0
- **WebSocket Server:** Laravel Reverb
- **Local Server:** XAMPP or Laragon (Apache + MySQL + PHP)
- **Planned Offline/Online Architecture:** Progressive Web App (PWA) with Service Worker + IndexedDB + Background Sync

## SYSTEM ARCHITECTURE
The system is built on a standard Model-View-Controller (MVC) architecture using Laravel, combined with a Progressive Web App (PWA) approach for offline capabilities (which is the part you still need to finish).
* The **Frontend** relies on Blade templates styled with Tailwind CSS, processed by Vite for hot-reloading and building assets.
* The **Backend** exposes standard web routes handled by controllers to interact with the database via Eloquent Models.
* The **Database** runs via MySQL through XAMPP or Laragon.

### Visual Representation of Planned Offline/Online Architecture
```text
┌─────────────────────────────────────────────────┐
│                 Tablet / Browser                  │
│  ┌──────────┐  ┌──────────┐  ┌───────────────┐  │
│  │ Service   │  │IndexedDB │  │ Background    │  │
│  │ Worker    │  │(Local    │  │ Sync          │  │
│  │ (Cache)   │  │ Database)│  │               │  │
│  └─────┬─────┘  └────┬─────┘  └──────┬────────┘  │
│        │             │               │           │
└────────┼─────────────┼───────────────┼───────────┘
         │             │               │
    ┌────┴─────────────┴───────────────┴────┐
    │         Internet Connection            │
    │    (Online ── syncs; Offline ── skips) │
    └────────────────┬──────────────────────┘
                     │
┌────────────────────┴──────────────────────┐
│              Laravel Server                 │
│  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │ Auth API │  │ REST API │  │ MySQL   │ │
│  │ (Sanctum)│  │Endpoints │  │Database │ │
│  └──────────┘  └──────────┘  └─────────┘ │
└────────────────────────────────────────────┘
```

## THINGS THAT NEED TO BE DONE
You want this app to work OFFLINE for health workers in areas with no internet, right? **THAT PART IS NOT BUILT YET!** 
Currently, the "Progressive Web App (PWA)" features are completely missing. Here is the EXACT checklist of things that need to be done next to finish this system:

- [ ] **1. Create a Service Worker (`public/sw.js`)**
  - **Why?** This file runs in the background of the browser and saves (caches) all your HTML, CSS, and Javascript. Without this, if the internet drops, your users will see a "No Internet" dinosaur screen.

- [ ] **2. Create a Web App Manifest (`public/manifest.json`)**
  - **Why?** This tells the tablet or phone that your website can be "installed" as a native app on their home screen.

- [ ] **3. Setup IndexedDB in Frontend (`resources/js/app.js`)**
  - **Why?** When the health worker is offline and types in new patient data, it can't go to your server. You must write JavaScript to save this data directly into the device's local database (IndexedDB) temporarily.

- [ ] **4. Build Sync API Endpoints (`routes/api.php`)**
  - **Why?** When the tablet reconnects to the internet, your frontend needs a way to send the saved offline data to your server. Your current `web.php` routes are for loading web pages, NOT for syncing raw background data. 

- [ ] **5. Implement Background Sync Logic**
  - **Why?** You MUST write logic that detects when the internet reconnects, pulls the data from IndexedDB, and sends it to the API endpoints you created in Step 4.

---

## HOW TO SET UP AND RUN THIS PROJECT RIGHT NOW

### Step 1: Get the Code
```bash
git clone https://github.com/HaggithCaray/maternal-healthcare.git
cd maternal-healthcare
```

### Step 2: Install Requirements
Install these programs on your computer first:
1. **XAMPP** or **Laragon** — Local server with Apache + MySQL + PHP 8.4
2. **Node.js** (v20+) and **NPM** — For building frontend assets (Tailwind CSS, Vite)

### Step 3: Start MySQL
- **XAMPP:** Open XAMPP Control Panel → Start **MySQL** (and **Apache** if using XAMPP's web server)
- **Laragon:** Open Laragon → Start All

MySQL will run on `127.0.0.1:3306` by default.

### Step 4: Create the Database
Open phpMyAdmin (XAMPP: `http://localhost/phpmyadmin`, Laragon: click "phpMyAdmin" in the menu) and create a database:
```sql
CREATE DATABASE healthcare_db;
```

### Step 5: Install PHP Dependencies
```bash
composer install
```

### Step 6: Setup Environment File
```bash
cp .env.example .env
```
*(Windows CMD: `copy .env.example .env`)*

Then update `.env` database settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=healthcare_db
DB_USERNAME=root
DB_PASSWORD=
```

### Step 7: Generate App Key & Migrate
```bash
php artisan key:generate
php artisan migrate --seed
```

### Step 8: Install Frontend Dependencies & Build
```bash
npm install
npm run build
```

### Step 9: Start the Servers
You need **two terminal windows** open:

**Terminal 1 — Laravel app:**
```bash
php artisan serve
```

**Terminal 2 — Reverb WebSocket server (for real-time chat):**
```bash
php artisan reverb:start
```

### Step 10: Open the App
Go to: **http://127.0.0.1:8000**

### Default Login
| Role | Email | Password |
|------|-------|----------|
| Healthcare Worker (Admin) | health@example.com | password |
| Patient | patient@example.com | password |
