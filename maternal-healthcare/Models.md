# Models

All models use Laravel 13's `#[Fillable([...])]` PHP attribute syntax.

## User
- **Traits:** HasFactory, Notifiable
- **Fillable:** name, email, password, role
- **Hidden:** password, remember_token
- **Methods:** `isAdmin()`, `isUser()`
- **No relationships defined** (used as sender/receiver in ChatMessage)

## Patient
- **Fillable:** user_id, first_name, last_name, dob, gender, phone, email, address, barangay, occupation, emergency_contact_name, emergency_contact_phone, registration_type, status
- **Computed:** `full_name` accessor
- **Relationships:**
  - `user()` → BelongsTo(User)
  - `maternalRecord()` → HasOne(MaternalRecord)
  - `childRecord()` → HasOne(ChildRecord)
  - `smsMessages()` → HasMany(SmsMessage)

## MaternalRecord
- **Fillable:** patient_id, lmp, edd, gravida, para, abortions, still_births, philhealth_number, blood_type, height_cm, allergies, medical_history, birth_plan
- **Casts:** lmp→date, edd→date, medical_history→array, birth_plan→array
- **Relationships:**
  - `patient()` → BelongsTo(Patient)
  - `checkups()` → HasMany(MaternalCheckup), ordered by visit_number ASC

## MaternalCheckup
- **Fillable:** maternal_record_id, visit_number, date, weight_kg, bp, age_of_gestation, fetal_heart_rate, attendant, status, notes, next_visit_date
- **Casts:** date→date, next_visit_date→date
- **Relationships:**
  - `maternalRecord()` → BelongsTo(MaternalRecord)

## ChildRecord
- **Fillable:** patient_id, mother_id, birth_weight_kg, birth_height_cm, head_circumference_cm, birth_type, delivery_type, delivery_place, attendant, birth_order, blood_type, has_newborn_screening, has_hearing_screening, has_eye_prophylaxis, has_vitamin_k, has_bcg_at_birth, has_hepb_at_birth
- **Casts:** All 6 `has_*` fields → boolean
- **Relationships:**
  - `patient()` → BelongsTo(Patient)
  - `mother()` → BelongsTo(Patient, 'mother_id')
  - `immunizations()` → HasMany(Immunization), ordered by scheduled_date ASC
  - `growthMeasurements()` → HasMany(GrowthMeasurement), ordered by date ASC

## Immunization
- **Fillable:** child_record_id, vaccine_name, dose_number, scheduled_date, given_date, administered_by, remarks, status
- **Casts:** scheduled_date→date, given_date→date
- **Relationships:**
  - `childRecord()` → BelongsTo(ChildRecord)

## GrowthMeasurement
- **Fillable:** child_record_id, date, age_months, weight_kg, height_cm, head_circumference_cm, status
- **Casts:** date→date
- **Relationships:**
  - `childRecord()` → BelongsTo(ChildRecord)

## SmsMessage
- **Fillable:** patient_id, phone_number, message, status, sent_at, type
- **Casts:** sent_at→datetime
- **Relationships:**
  - `patient()` → BelongsTo(Patient)

## ChatMessage
- **Fillable:** sender_id, receiver_id, message, is_read, attachment_path, attachment_name, attachment_type
- **Appended:** attachment_url (computed accessor)
- **Casts:** is_read→boolean
- **Methods:** `isImage()`, `isVideo()`, `isDocument()`, `getAttachmentUrlAttribute()`
- **Relationships:**
  - `sender()` → BelongsTo(User, 'sender_id')
  - `receiver()` → BelongsTo(User, 'receiver_id')

## Related Pages
- [[Entity Relationships]]
- [[Database Migrations]]
