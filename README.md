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
- **Database:** MySQL 8.0 (Docker)
- **Infrastructure:** Docker Compose (Nginx + PHP-FPM + MySQL + phpMyAdmin)
- **Planned Offline/Online Architecture:** Progressive Web App (PWA) with Service Worker + IndexedDB + Background Sync

## SYSTEM ARCHITECTURE
The system is built on a standard Model-View-Controller (MVC) architecture using Laravel, combined with a Progressive Web App (PWA) approach for offline capabilities (which is the part you still need to finish).
* The **Frontend** relies on Blade templates styled with Tailwind CSS, processed by Vite for hot-reloading and building assets.
* The **Backend** exposes standard web routes handled by controllers to interact with the database via Eloquent Models.
* The **Database** and servers are run inside Docker containers using MySQL 8.0 and Nginx for consistency across environments.

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

## HOW TO SET UP AND RUN THIS PROJECT FROM SCRATCH WITH DOCKER (RECOMMENDED)

This guide walks you through downloading the project, switching to the correct Git branch, and running the app inside Docker from a completely clean start. **You do NOT need to install PHP or Composer on your computer** — everything runs inside containers.

> The older instructions further below describe the legacy "shared folder" setup. The steps in this section are the current, recommended way (the application code is built *into* the Docker images, which makes pages load much faster on Windows).

### Step 1: Clone the repository
Open your terminal (PowerShell or CMD on Windows, or Bash on macOS/Linux):
```bash
git clone https://github.com/HaggithCaray/maternal-healthcare.git
cd maternal-healthcare
```

### Step 2: Switch to the branch you want to run
The project has several branches, each representing a different milestone of the system:
- `main` — the full (advanced) version of the app
- `milestone/10-percent` — the stripped-down foundation version (environment, authentication, dashboard, records, patient registration)

List all available branches:
```bash
git branch -a
```

Switch to the branch you want (e.g., the 10% foundation version):
```bash
git checkout milestone/10-percent
```

To create a new branch based on the current one:
```bash
git checkout -b my-new-branch
```

### Step 3: Create the `.env` file
The app needs a configuration file. Copy the template:
- **Windows:** `copy .env.example .env`
- **macOS / Linux:** `cp .env.example .env`

The template already ships with the correct database settings for Docker (MySQL, host `db`, database `healthcare_db`). You normally do NOT need to change anything, but double-check that these lines exist:
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=healthcare_db
DB_USERNAME=healthcare_user
DB_PASSWORD=healthcare123
```

### Step 4: Install frontend dependencies and build assets (requires Node.js)
The compiled CSS and JavaScript must exist before the Docker images are built:
```bash
npm install
npm run build
```

### Step 5: Build and start the containers
```bash
docker compose up -d --build
```
What happens during this step:
- The application code is copied *into* the images (this is what makes it fast — no slow file sharing between Windows and the container).
- If the `vendor/` folder does not exist yet, Composer dependencies are installed automatically during the build.
- If your `.env` has an empty `APP_KEY`, a valid key is generated automatically.
- The MySQL database and phpMyAdmin containers also start.

The first build takes a few minutes; later rebuilds are much faster because Docker caches the layers.

### Step 6: Create the database tables and seed sample data
```bash
docker exec healthcare-app php artisan migrate --seed --force
```
The `--seed` flag inserts demo data (sample mothers, children, and login accounts).

### Step 7: Open the app
Open your browser and go to **http://localhost:8080**.

Additional addresses:
- **phpMyAdmin** (visual database browser): http://localhost:8081 — user `healthcare_user`, password `healthcare123`
- **MySQL** is exposed on host port `3307`

Default accounts (created by the seeder):
| Role | Email | Password |
| --- | --- | --- |
| Admin | `health@example.com` | `password` |
| Patient | `patient@example.com` | `password` |

### Step 8: Run the automated tests (optional)
```bash
docker exec healthcare-app php artisan test
```

---

### Everyday commands
- **See your code changes after editing:** `docker compose up -d --build` (the images must be rebuilt because the code lives inside them)
- **Stop the containers:** `docker compose down`
- **Start them again later:** `docker compose up -d`
- **Reset the database to a clean state:** `docker exec healthcare-app php artisan migrate:fresh --seed --force`
- **View logs:** `docker logs healthcare-app` (or `healthcare-web`)

### Switching to another branch later
If the app is already running and you want to switch to a different branch:
```bash
git checkout <branch-name>     # e.g. git checkout main
npm run build                  # only needed if frontend assets changed
docker compose up -d --build   # rebuilds the images with the new code
```
The database keeps its existing data when you switch branches, so apply any new migrations with:
```bash
docker exec healthcare-app php artisan migrate --force
```
If the other branch changed the database structure significantly, reset it instead:
```bash
docker exec healthcare-app php artisan migrate:fresh --seed --force
```

---

## HOW TO SET UP AND RUN THIS PROJECT RIGHT NOW

Since you have limited development knowledge, follow these steps EXACTLY as written to get the app running on your computer.

### Step 1: Get the Code
First, you need to download this project to your computer. Open your terminal or command prompt and run:
```bash
git clone https://github.com/HaggithCaray/maternal-healthcare.git
cd maternal-healthcare
```

### Step 2: Install Requirements
You MUST install these programs on your computer first to start from scratch:
1. **Docker Desktop** (v24 or higher) - REQUIRED to run the database and web server containers.
2. **Node.js** (v20 or higher) - REQUIRED for building the frontend assets.
3. **NPM** (comes with Node.js) - REQUIRED to install Tailwind CSS and Vite dependencies.

*NOTE: You do NOT need to install PHP or Composer on your local machine if you are using Docker, because the Docker container handles the PHP environment for you.*

### Step 3: Start the Server
Open your terminal or command prompt, make sure you are inside the `maternal-healthcare` folder, and run:
```bash
docker compose up -d
```
*This starts your database and web server in the background.*

### Step 4: Install PHP Dependencies
Run this command to install the backend tools:
```bash
docker exec -it healthcare-app composer install
```

### Step 5: Setup Environment File (`.env`)
You MUST configure your database connection so the app can talk to the database.

1. **Create the file:**
   Run this command in your terminal to copy the template:
   ```bash
   cp .env.example .env
   ```
   *(If on Windows CMD, do `copy .env.example .env` instead)*

2. **Open the `.env` file** in your code editor.
3. **Generate an App Key:**
   Run this command in your terminal:
   ```bash
   docker exec -it healthcare-app php artisan key:generate
   ```
4. **Change the Database Settings:**
   Find the lines that start with `DB_` (around line 23) and change them to look EXACTLY like this (make sure you remove the `#` symbols at the start of the lines!):

   ```env
   DB_CONNECTION=mysql
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=healthcare_db
   DB_USERNAME=healthcare_user
   DB_PASSWORD=healthcare123
   ```
   *Why these values? Because this matches the exact setup in your `docker-compose.yml` file!*

### Step 6: Create the Database Tables
Run this command to build the tables inside your database:
```bash
docker exec -it healthcare-app php artisan migrate
```

### Step 7: Install Frontend Dependencies & Build
Run these commands to install Tailwind CSS and build the visual styling:
```bash
npm install
npm run build
```

### Step 8: Open the App
Go to your web browser and open:
**http://localhost:8080**

---

