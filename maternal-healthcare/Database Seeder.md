# Database Seeder

## Overview
`database/seeders/DatabaseSeeder.php` creates realistic sample data for development.

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
php artisan db:seed

# Fresh start
php artisan migrate:fresh --seed
```

## Related Pages
- [[Setup Guide]]
- [[Entity Relationships]]
