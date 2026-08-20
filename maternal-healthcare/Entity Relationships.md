# Entity Relationships

## ER Diagram
```
User (admin/user)
  │
  ├──< ChatMessage (sender_id, receiver_id) >── User
  │
  └──< Patient (user_id)  [optional link]
        │
        ├── MaternalRecord (1:1)
        │     └──< MaternalCheckup (1:many)
        │
        ├── ChildRecord (1:1)
        │     ├── mother_id → Patient (the mother)
        │     ├──< Immunization (1:many)
        │     └──< GrowthMeasurement (1:many)
        │
        └──< SmsMessage (1:many)
```

## Models

### User
| Field | Type | Notes |
|-------|------|-------|
| name | string | |
| email | string | unique |
| password | string | hashed, hidden |
| role | string | 'admin' or 'user' |
| isAdmin() | method | checks role === 'admin' |

### Patient
| Field | Type | Notes |
|-------|------|-------|
| user_id | FK nullable | optional link to User |
| first_name, last_name | string | |
| dob | date | |
| gender | string | Female/Male |
| phone, email | string | |
| address, barangay | string | barangay defaults to 'Bicao' |
| occupation | string | |
| emergency_contact_name, phone | string | |
| registration_type | string | 'Maternal' or 'Child' |
| status | string | Active, Due for Visit, High Risk, Completed |

### MaternalRecord
| Field | Type | Notes |
|-------|------|-------|
| patient_id | FK cascade | |
| lmp, edd | date | EDD auto-computed from LMP + 280 days |
| gravida, para | int | |
| abortions, still_births | int | |
| philhealth_number | string | |
| blood_type, height_cm | string/int | |
| allergies | string | |
| medical_history | array (JSON) | Hypertension, Diabetes, Asthma, Heart Disease, Anemia, Multiple Births |
| birth_plan | array (JSON) | |

### MaternalCheckup
| Field | Type | Notes |
|-------|------|-------|
| maternal_record_id | FK cascade | |
| visit_number | int | |
| date | date | |
| weight_kg | decimal | |
| bp | string | e.g. "120/80" |
| age_of_gestation | string | |
| fetal_heart_rate | string | |
| attendant | string | |
| status | string | default 'Healthy' |
| notes | string | |
| next_visit_date | date | |

### ChildRecord
| Field | Type | Notes |
|-------|------|-------|
| patient_id | FK cascade | |
| mother_id | FK nullable | links to mother's Patient |
| birth_weight_kg, birth_height_cm | decimal | |
| head_circumference_cm | decimal | |
| birth_type, delivery_type | string | |
| delivery_place, attendant | string | |
| birth_order, blood_type | string | |
| has_newborn_screening | boolean | |
| has_hearing_screening | boolean | |
| has_eye_prophylaxis | boolean | |
| has_vitamin_k | boolean | |
| has_bcg_at_birth | boolean | |
| has_hepb_at_birth | boolean | |

### Immunization
| Field | Type | Notes |
|-------|------|-------|
| child_record_id | FK cascade | |
| vaccine_name | string | BCG, HepB, Pentavalent, OPV, IPV, PCV, MMR |
| dose_number | int | |
| scheduled_date | date | |
| given_date | date | nullable |
| administered_by | string | |
| remarks | string | |
| status | string | Scheduled, Given, Overdue |

### GrowthMeasurement
| Field | Type | Notes |
|-------|------|-------|
| child_record_id | FK cascade | |
| date | date | |
| age_months | int | |
| weight_kg | decimal | |
| height_cm | decimal | |
| head_circumference_cm | decimal | |
| status | string | default 'Normal' |

### SmsMessage
| Field | Type | Notes |
|-------|------|-------|
| patient_id | FK cascade | |
| phone_number | string | |
| message | string | |
| status | string | Pending, Sent, Failed |
| sent_at | datetime | |
| type | string | default 'Reminder' |

### ChatMessage
| Field | Type | Notes |
|-------|------|-------|
| sender_id | FK users | |
| receiver_id | FK users | |
| message | string | nullable (attachment-only messages) |
| is_read | boolean | default false |
| attachment_path | string | |
| attachment_name | string | |
| attachment_type | string | |
| attachment_url | accessor | computed from attachment_path |

## Related Pages
- [[Architecture]]
- [[Database Migrations]]
