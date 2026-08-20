# Database Migrations

13 migration files in `database/migrations/`.

## Tables

### users (000000)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| name | varchar | |
| email | varchar | unique |
| email_verified_at | timestamp | nullable |
| password | varchar | |
| remember_token | varchar | nullable |
| role | varchar | added in 2026_06_25 migration, default 'user' |

### cache (000001)
Standard Laravel cache + cache_locks tables.

### jobs (000002)
Standard Laravel jobs, job_batches, failed_jobs tables.

### patients (2026_07_05_000001)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| user_id | bigint FK nullable | → users.id |
| first_name, last_name | varchar | |
| dob | date | |
| gender | varchar | |
| phone, email | varchar | nullable |
| address | varchar | |
| barangay | varchar | default 'Bicao' |
| occupation | varchar | nullable |
| emergency_contact_name, phone | varchar | |
| registration_type | varchar | 'Maternal' or 'Child' |
| status | varchar | default 'Active' |

### maternal_records (2026_07_05_000002)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| patient_id | bigint FK | → patients.id, cascade |
| lmp, edd | date | |
| gravida, para | int | |
| abortions, still_births | int | nullable |
| philhealth_number | varchar | nullable |
| blood_type | varchar | nullable |
| height_cm | int | nullable |
| allergies | text | nullable |
| medical_history | json | nullable |
| birth_plan | json | nullable |

### maternal_checkups (2026_07_05_000003)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| maternal_record_id | bigint FK | → maternal_records.id, cascade |
| visit_number | int | |
| date | date | |
| weight_kg | decimal | nullable |
| bp | varchar | nullable |
| age_of_gestation | varchar | nullable |
| fetal_heart_rate | varchar | nullable |
| attendant | varchar | nullable |
| status | varchar | default 'Healthy' |
| notes | text | nullable |
| next_visit_date | date | nullable |

### child_records (2026_07_05_000004)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| patient_id | bigint FK | → patients.id, cascade |
| mother_id | bigint FK nullable | → patients.id |
| birth_weight_kg, birth_height_cm | decimal | nullable |
| head_circumference_cm | decimal | nullable |
| birth_type, delivery_type | varchar | nullable |
| delivery_place, attendant | varchar | nullable |
| birth_order, blood_type | varchar | nullable |
| has_newborn_screening | boolean | default false |
| has_hearing_screening | boolean | default false |
| has_eye_prophylaxis | boolean | default false |
| has_vitamin_k | boolean | default false |
| has_bcg_at_birth | boolean | default false |
| has_hepb_at_birth | boolean | default false |

### immunizations (2026_07_05_000005)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| child_record_id | bigint FK | → child_records.id, cascade |
| vaccine_name | varchar | |
| dose_number | int | |
| scheduled_date | date | |
| given_date | date | nullable |
| administered_by | varchar | nullable |
| remarks | text | nullable |
| status | varchar | default 'Scheduled' |

### growth_measurements (2026_07_05_000006)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| child_record_id | bigint FK | → child_records.id, cascade |
| date | date | |
| age_months | int | |
| weight_kg | decimal | |
| height_cm | decimal | |
| head_circumference_cm | decimal | nullable |
| status | varchar | default 'Normal' |

### sms_messages (2026_07_05_000007)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| patient_id | bigint FK | → patients.id, cascade |
| phone_number | varchar | |
| message | text | |
| status | varchar | default 'Pending' |
| sent_at | timestamp | nullable |
| type | varchar | default 'Reminder' |

### chat_messages (2026_07_05_000008 + 2026_07_09 alter)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto |
| sender_id | bigint FK | → users.id |
| receiver_id | bigint FK | → users.id |
| message | text | nullable (attachment-only) |
| is_read | boolean | default false |
| attachment_path | varchar | nullable, added in alter |
| attachment_name | varchar | nullable, added in alter |
| attachment_type | varchar | nullable, added in alter |

## Related Pages
- [[Entity Relationships]]
- [[Architecture]]
