# Database Seeder

## Overview
There are two seeders:

- `database/seeders/DatabaseSeeder.php` (the default `db:seed`) is **production-safe**: it creates
  only the first admin from `ADMIN_EMAIL` / `ADMIN_PASSWORD` (optional `ADMIN_NAME`, password at
  least 12 characters) and does nothing if an admin already exists.
- `database/seeders/DemoSeeder.php` creates the realistic sample data described below, for
  development only: `php artisan db:seed --class=DemoSeeder`. Its accounts use the password
  `password`, so never run it on a server.

## Seeded Data

### Users
| Name | Email | Password | Role |
|------|-------|----------|------|
| Health Worker | health@example.com | password | admin |
| Elena Dela Cruz | patient@example.com | password | user |

### Patients

#### 1. Elena Dela Cruz (Maternal, Active)
- **DOB:** 1995-03-15
- **User:** linked to patient@example.com
- **Maternal Record:** LMP 2026-01-15, Gravida 2, Para 1
- **Medical History:** Diabetes
- **3 Checkups:** Visit 1 (Healthy), Visit 2 (Healthy), Visit 3 (Healthy)
- **1 SMS:** Appointment reminder
- **2 Children:** Liam Andres, Sofia Garcia

#### 2. Liam Andres (Child, 9 months)
- **DOB:** 2025-10-01
- **Mother:** Elena Dela Cruz
- **Birth:** 3.2kg, 50cm
- **Immunizations:** BCG, HepB, Pentavalent x3, OPV x3, IPV, PCV x3 = Given; MMR = Scheduled
- **Growth:** 5 measurements over 9 months

#### 3. Sofia Garcia (Child, 2 months)
- **DOB:** 2026-05-01
- **Mother:** Elena Dela Cruz
- **Birth:** 2.9kg, 48cm
- **Immunizations:** BCG, HepB = Given; Pentavalent #1 = Given; rest Scheduled
- **Growth:** 3 measurements over 2 months

#### 4. Maria Santos-Dizon (Maternal, High Risk)
- **DOB:** 1998-08-10
- **Maternal Record:** LMP 2025-12-01, Gravida 3, Para 2
- **Medical History:** Hypertension, Asthma, Anemia
- **2 Checkups:** Visit 1 (Healthy), Visit 2 (Risk - elevated BP)

### Chat Messages
3 messages in Filipino/Tagalog between Elena and the health worker about prenatal vitamins.

## Running the Seeder
```bash
# Production / first install: only the first admin
ADMIN_EMAIL=you@example.com ADMIN_PASSWORD='a-long-random-password' php artisan db:seed

# Development: sample data
php artisan db:seed --class=DemoSeeder

# Development fresh start with sample data
php artisan migrate:fresh && php artisan db:seed --class=DemoSeeder
```

## Related Pages
- [[Setup Guide]]
- [[Entity Relationships]]
