# Local Development Setup

## Repositories
| Repo | URL | Terminal Setup |
|------|-----|----------------|
| **M-healthcare** (Base) | https://github.com/HaggithCaray/M-healthcare.git | 1 terminal (`php artisan serve`) |
| **maternal-healthcare** (Advanced) | https://github.com/HaggithCaray/maternal-healthcare.git | 2 terminals (app + Reverb) |

## Overview
The app runs locally using XAMPP for MySQL and Apache, with Laravel's built-in server and Reverb WebSocket server.

## Services
| Service | Command | Port | Purpose |
|---------|---------|------|---------|
| Laravel App | `php artisan serve` | 8000 | Main web application |
| Reverb WebSocket | `php artisan reverb:start` | 8080 | Real-time messaging |
| MySQL | XAMPP | 3306 | Database |
| phpMyAdmin | XAMPP | 80 | Database admin UI |

## Two-Terminal Setup
```bash
# Terminal 1: Laravel app
php artisan serve

# Terminal 2: WebSocket server (for real-time chat)
php artisan reverb:start
```

## Database Credentials
| Repo | Database | User | Password |
|------|----------|------|----------|
| M-healthcare (Base) | healthcare_base_db | root | (empty) |
| maternal-healthcare (Advanced) | healthcare_db | root | (empty) |

## MySQL Setup
1. Start MySQL in XAMPP Control Panel
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Create database:
```sql
-- M-healthcare (Base)
CREATE DATABASE healthcare_base_db;

-- maternal-healthcare (Advanced)
CREATE DATABASE healthcare_db;
```

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

# Fresh start with seed data
php artisan migrate:fresh --seed

# Tinker
php artisan tinker
```

## Related Pages
- [[Setup Guide]]
- [[Architecture]]
