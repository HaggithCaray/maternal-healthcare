# Setup Guide

## Which Repo?

| Repo                               | URL                                                       | What's Included                                                                               |
| ---------------------------------- | --------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| **M-healthcare** (Base)            | `https://github.com/HaggithCaray/M-healthcare.git`        | Auth, dashboard, patient CRUD, records, patient portal (19 tests)                             |
| **maternal-healthcare** (Advanced) | `https://github.com/HaggithCaray/maternal-healthcare.git` | Everything above + maternal/child records, immunizations, SMS, chat, PWA, reports (47+ tests) |

### Setup for M-healthcare (Base)
```bash
git clone https://github.com/HaggithCaray/M-healthcare.git
cd M-healthcare
composer install
cp .env.example .env
php artisan key:generate
npm install && npm run build
php artisan migrate --seed
php artisan serve
```
Only one terminal needed — no Reverb/WebSocket.

### Setup for maternal-healthcare (Advanced)
```bash
git clone https://github.com/HaggithCaray/maternal-healthcare.git
cd maternal-healthcare
composer install
cp .env.example .env
php artisan key:generate
npm install && npm run build
php artisan migrate --seed
```
Two terminals needed:
```bash
php artisan serve          # Terminal 1
php artisan reverb:start   # Terminal 2 (WebSocket for chat)
```

## Prerequisites
- **XAMPP** (Apache + MySQL + PHP 8.4)
- **Node.js** (v20+) and **NPM**
- **Composer**
- **Git**

## Quick Start
```bash
# 1. Clone the repo
git clone https://github.com/HaggithCaray/maternal-healthcare.git
cd maternal-healthcare

# 2. Install PHP dependencies
composer install

# 3. Set up environment file
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env (see Database Setup below)

# 5. Run migrations and seed data
php artisan migrate --seed

# 6. Install frontend dependencies and build CSS/JS
npm install
npm run build

# 7. Start the app (two terminals)
php artisan serve          # Terminal 1 — http://127.0.0.1:8000
php artisan reverb:start   # Terminal 2 — WebSocket
```

## Database Setup
1. Start MySQL via XAMPP Control Panel
2. Create the appropriate database:

**M-healthcare (Base):**
```sql
CREATE DATABASE healthcare_base_db;
```

**maternal-healthcare (Advanced):**
```sql
CREATE DATABASE healthcare_db;
```

3. Update `.env` — make sure these values are set:

**M-healthcare (Base):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=healthcare_base_db
DB_USERNAME=root
DB_PASSWORD=
```

**maternal-healthcare (Advanced):**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=healthcare_db
DB_USERNAME=root
DB_PASSWORD=
```

4. Run migrations and seed data:
```bash
php artisan migrate --seed
```

## Frontend Setup
```bash
npm install
npm run build
```

## Starting the App
Open **two terminal windows**:

**Terminal 1 — Laravel app:**
```bash
php artisan serve
```

**Terminal 2 — Reverb WebSocket (for real-time chat):**
```bash
php artisan reverb:start
```

## URLs
| Service | URL |
|---------|-----|
| Laravel App | http://127.0.0.1:8000 |
| phpMyAdmin | http://localhost/phpmyadmin |
| Reverb WebSocket | ws://localhost:8080 |

## Default Credentials
### Admin Login
- **Email:** health@example.com
- **Password:** password
- **Role:** Healthcare Worker

### Patient Login
- **Email:** patient@example.com
- **Role:** Patient

---

## XAMPP Setup (Specific)

### Step 1: Install XAMPP
1. Download from https://www.apachefriends.org
2. Install to `C:\xampp`
3. Open XAMPP Control Panel

### Step 2: Upgrade PHP 8.2 → 8.4
XAMPP ships with PHP 8.2. The project requires PHP ^8.3. Upgrade to 8.4:

1. Stop Apache and MySQL in XAMPP Control Panel

2. Download PHP 8.4 TS (Thread Safe) — required for Apache:
   ```
   https://downloads.php.net/~windows/releases/php-8.4.24-Win32-vs17-x64.zip
   ```
   **Important:** The filename WITHOUT `nts-` prefix is the TS (Thread Safe) version. Do NOT download the `nts-` version — it will not work with Apache.

3. Rename existing PHP folder:
   ```
   C:\xampp\php  →  C:\xampp\php-8.2-backup
   ```

4. Extract the downloaded PHP 8.4 zip to:
   ```
   C:\xampp\php
   ```

5. Copy `php.ini` from backup:
   ```
   Copy C:\xampp\php-8.2-backup\php.ini  →  C:\xampp\php\php.ini
   ```

6. Fix `php.ini` — open in Notepad, find and **comment out** this line:
   ```
   ;browscap="C:\xampp\php\extras\browscap.ini"
   ```
   Add a `;` at the start. Without this fix, PHP will crash on startup.

7. Verify extensions are enabled in `php.ini` — find each line and make sure it does NOT start with `;`:
   ```ini
   extension_dir = "C:\xampp\php\ext"
   extension=curl
   extension=gd
   extension=mbstring
   extension=mysqli
   extension=pdo_mysql
   extension=pdo_sqlite
   extension=sqlite3
   extension=fileinfo
   extension=bz2
   extension=intl
   extension=sockets
   extension=sodium
   extension=zip
   ```
   If any line starts with `;` (e.g. `;extension=mysqli`), remove the `;` to enable it.

8. Add PHP to system PATH (run in PowerShell as Administrator):
   ```powershell
   [System.Environment]::SetEnvironmentVariable("Path", [System.Environment]::GetEnvironmentVariable("Path", "User") + ";C:\xampp\php", "User")
   ```

9. Open a **NEW terminal**, verify:
   ```powershell
   php -v
   ```
   Should show `PHP 8.4.24 (cli)`.

### Step 3: Fix Apache Port Conflict
If port 80 is blocked by another service (e.g. Windows IIS), change Apache to port 8080:

1. Open `C:\xampp\apache\conf\httpd.conf` in Notepad
2. Find and change:
   ```
   Listen 80  →  Listen 8080
   ```
3. Find and change:
   ```
   ServerName localhost:80  →  ServerName localhost:8080
   ```
4. Save, restart Apache
5. Access phpMyAdmin at `http://localhost:8080/phpmyadmin`

If port 80 is free (IIS disabled), keep default settings — no changes needed.

### Step 4: Fix curl/libssh2 DLL Conflict
Apache has an older `libssh2.dll` that conflicts with PHP 8.4's curl extension. Copy the newer DLLs:

```powershell
Copy-Item "C:\xampp\php\libssh2.dll" "C:\xampp\apache\bin\libssh2.dll" -Force
Copy-Item "C:\xampp\php\brotlidec.dll" "C:\xampp\apache\bin\brotlidec.dll" -Force
Copy-Item "C:\xampp\php\brotlicommon.dll" "C:\xampp\apache\bin\brotlicommon.dll" -Force
```

Without this, you'll get: `The procedure entry point libssh2_crypto_engine could not be located`

### Step 5: Disable SSL Config
Comment out the SSL include in `C:\xampp\apache\conf\httpd.conf`:
```
#Include conf/extra/httpd-ssl.conf
```
This prevents the `Bad Request — speaking plain HTTP to an SSL-enabled server port` error.

### Step 6: Start MySQL
1. Open XAMPP Control Panel
2. Click **Start** next to **MySQL**

### Step 7: Create Database
```powershell
# M-healthcare (Base)
mysql -u root -e "CREATE DATABASE healthcare_base_db;"

# maternal-healthcare (Advanced)
mysql -u root -e "CREATE DATABASE healthcare_db;"
```

### Step 8: phpMyAdmin
phpMyAdmin is included in XAMPP:
- Go to `http://localhost/phpmyadmin`
- Login: **root** / **(blank password)**

---

## Common Commands
```powershell
# Run tests
php artisan test

# Run specific test
php artisan test --filter="test_admin_can_update_maternal_patient"

# Clear caches
php artisan view:clear
php artisan cache:clear
php artisan config:clear

# Run seeder
php artisan db:seed

# Tinker
php artisan tinker

# If PHP is not recognized, run this first:
$env:Path += ";C:\xampp\php"
```

## Related Pages
- [[Project Overview]]
- [[Local Development Setup]]
