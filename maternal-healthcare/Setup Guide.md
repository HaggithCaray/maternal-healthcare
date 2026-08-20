# Setup Guide

## Prerequisites
- **XAMPP** (Apache + MySQL + PHP 8.4)
- **Node.js** (v20+) and **NPM**
- **Composer**
- **Git**

## Quick Start
```bash
git clone <repo-url>
cd maternal-health-care
composer install
cp .env.example .env
php artisan key:generate
```

## Database Setup
1. Start MySQL via XAMPP
2. Create database:
```sql
CREATE DATABASE healthcare_db;
```
3. Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=healthcare_db
DB_USERNAME=root
DB_PASSWORD=
```
4. Run migrations:
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
| Reverb WebSocket | ws://localhost:8080 |
| phpMyAdmin | http://localhost/phpmyadmin |

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

### Install XAMPP
1. Download from https://www.apachefriends.org
2. Install to `C:\xampp` with **PHP 8.2** (default available)
3. Open XAMPP Control Panel

### Upgrade PHP to 8.4
XAMPP ships with PHP 8.2. To upgrade to 8.4:

1. Download PHP 8.4 NTS (Non Thread Safe) for Windows:
   ```
   https://windows.php.net/downloads/releases/php-8.4.24-nts-Win32-vs16-x64.zip
   ```

2. Rename existing PHP folder:
   ```
   C:\xampp\php  →  C:\xampp\php-8.2-backup
   ```

3. Extract the downloaded PHP 8.4 zip to:
   ```
   C:\xampp\php
   ```

4. Copy `php.ini` from backup:
   ```
   Copy C:\xampp\php-8.2-backup\php.ini  →  C:\xampp\php\php.ini
   ```

5. Update `php.ini` — find and change these:
   ```ini
   extension_dir = "C:\xampp\php\ext"
   error_reporting = E_ALL
   display_errors = On
   ```

6. Enable required extensions in `php.ini` (uncomment these lines):
   ```
   extension=curl
   extension=gd
   extension=mbstring
   extension=mysqli
   extension=pdo_mysql
   extension=pdo_sqlite
   extension=sqlite3
   extension=fileinfo
   extension=openssl
   extension=bz2
   extension=intl
   extension=sockets
   extension=pcntl
   extension=sodium
   extension=zip
   ```

7. Copy these files from `C:\xampp\php-8.2-backup` to `C:\xampp\php`:
   ```
   libeay32.dll
   ssleay32.dll
   libssl.dll
   ```

8. Add PHP to system PATH:
   ```powershell
   [System.Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\xampp\php", "Machine")
   ```

9. Verify in a **new terminal**:
   ```bash
   php -v
   ```
   Should show `PHP 8.4.24`.

### Start Services
1. Open XAMPP Control Panel
2. Click **Start** next to **MySQL**
3. Click **Start** next to **Apache** (optional — can use `php artisan serve` instead)

### PHP Path (XAMPP)
XAMPP PHP is at:
```
C:\xampp\php\php.exe
```

If `php` is not recognized, run this first:
```powershell
$env:Path += ";C:\xampp\php"
```

### phpMyAdmin (XAMPP)
phpMyAdmin is included in XAMPP:
- Go to `http://localhost/phpmyadmin`
- Login: **root** / **(blank password)**

### Database Management

#### Option 1: phpMyAdmin (GUI)
1. Open http://localhost/phpmyadmin
2. Login: **root** / **(blank)**
3. Click **Databases** → Create `healthcare_db`

#### Option 2: Terminal (Fastest)
```bash
mysql -u root -e "CREATE DATABASE healthcare_db;"
```

---

## Common Commands
```bash
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
```

## Related Pages
- [[Project Overview]]
- [[Local Development Setup]]
