# Local Development Setup

## Overview
The app runs locally using XAMPP or Laragon for MySQL, with Laravel's built-in server and Reverb WebSocket server.

## Services
| Service | Command | Port | Purpose |
|---------|---------|------|---------|
| Laravel App | `php artisan serve` | 8000 | Main web application |
| Reverb WebSocket | `php artisan reverb:start` | 8080 | Real-time messaging |
| MySQL | XAMPP/Laragon | 3306 | Database |
| phpMyAdmin | XAMPP/Laragon | — | Database admin UI |

## Two-Terminal Setup
```bash
# Terminal 1: Laravel app
php artisan serve

# Terminal 2: WebSocket server (for real-time chat)
php artisan reverb:start
```

## Database Credentials
| User | Password | Database |
|------|----------|----------|
| root | (empty) | healthcare_db |

## MySQL Setup
1. Start MySQL in XAMPP Control Panel or Laragon
2. Open phpMyAdmin: `http://localhost/phpmyadmin`
3. Create database: `CREATE DATABASE healthcare_db;`

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
