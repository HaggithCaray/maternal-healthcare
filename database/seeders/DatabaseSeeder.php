<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Patient;
use App\Models\MaternalRecord;
use App\Models\MaternalCheckup;
use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\GrowthMeasurement;
use App\Models\SmsMessage;
use App\Models\ChatMessage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Health Worker (Admin)
        $admin = User::create([
            'name' => 'Health Worker',
            'email' => 'health@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Create Patient User Account (linked to Elena Dela Cruz)
        $elenaUser = User::create([
            'name' => 'Elena Dela Cruz',
            'email' => 'patient@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        // 3. Create Patient Profiles
        // Mother: Elena Dela Cruz
        $elena = Patient::create([
            'user_id' => $elenaUser->id,
            'first_name' => 'Elena',
            'last_name' => 'Dela Cruz',
            'dob' => '1995-05-12',
            'gender' => 'Female',
            'phone' => '09171234567',
            'email' => 'patient@example.com',
            'address' => 'Purok 2, Barangay Bicao',
            'barangay' => 'Bicao',
            'occupation' => 'Housewife',
            'emergency_contact_name' => 'Juan Dela Cruz',
            'emergency_contact_phone' => '09187654321',
            'registration_type' => 'Maternal',
            'status' => 'Active',
        ]);

        // Maternal Record for Elena
        $elenaMaternal = MaternalRecord::create([
            'patient_id' => $elena->id,
            'lmp' => '2026-01-15',
            'edd' => '2026-10-22',
            'gravida' => 2,
            'para' => 1,
            'abortions' => 0,
            'still_births' => 0,
            'philhealth_number' => '12-345678901-2',
            'blood_type' => 'O+',
            'height_cm' => 152.5,
            'allergies' => 'None',
            'medical_history' => [
                'Hypertension' => false,
                'Diabetes' => false,
                'Asthma' => false,
                'Heart Disease' => false,
                'Anemia' => false,
                'Multiple Births' => false
            ],
            'birth_plan' => [
                'facility' => 'Barangay Bicao Health Station',
                'attendant' => 'Midwife Elena',
                'helper' => 'Juan Dela Cruz',
                'phone' => '09187654321',
                'transport' => 'Tricycle',
                'blood_donor' => 'Pedro Dela Cruz',
            ],
        ]);

        // Checkups for Elena
        MaternalCheckup::create([
            'maternal_record_id' => $elenaMaternal->id,
            'visit_number' => 1,
            'date' => '2026-03-12',
            'weight_kg' => 54.2,
            'bp' => '110/70',
            'age_of_gestation' => '8w 1d',
            'fetal_heart_rate' => 140,
            'attendant' => 'Midwife Elena',
            'status' => 'Healthy',
            'notes' => 'First checkup. Given prenatal vitamins and tetanus counseling.',
            'next_visit_date' => '2026-04-15',
        ]);

        MaternalCheckup::create([
            'maternal_record_id' => $elenaMaternal->id,
            'visit_number' => 2,
            'date' => '2026-04-15',
            'weight_kg' => 56.5,
            'bp' => '115/75',
            'age_of_gestation' => '13w 0d',
            'fetal_heart_rate' => 144,
            'attendant' => 'Midwife Elena',
            'status' => 'Healthy',
            'notes' => 'Second checkup. Routine vitals normal.',
            'next_visit_date' => '2026-05-20',
        ]);

        MaternalCheckup::create([
            'maternal_record_id' => $elenaMaternal->id,
            'visit_number' => 3,
            'date' => '2026-05-20',
            'weight_kg' => 58.9,
            'bp' => '118/76',
            'age_of_gestation' => '18w 1d',
            'fetal_heart_rate' => 146,
            'attendant' => 'Midwife Elena',
            'status' => 'Healthy',
            'notes' => 'Third checkup. Fetal movement felt.',
            'next_visit_date' => '2026-07-15',
        ]);

        // Outgoing SMS for Elena
        SmsMessage::create([
            'patient_id' => $elena->id,
            'phone_number' => '09171234567',
            'message' => 'Good day Nanay Elena! This is a reminder for your upcoming prenatal checkup on July 15, 2026. Please bring your Nanay Book. Keep healthy!',
            'status' => 'Sent',
            'sent_at' => Carbon::now()->subDays(2),
            'type' => 'Reminder',
        ]);

        // Children of Elena
        // Child 1: Liam Andres (9 months old)
        $liamPatient = Patient::create([
            'first_name' => 'Liam',
            'last_name' => 'Andres',
            'dob' => '2025-10-05',
            'gender' => 'Male',
            'phone' => '09171234567',
            'email' => null,
            'address' => 'Purok 2, Barangay Bicao',
            'barangay' => 'Bicao',
            'registration_type' => 'Child',
            'status' => 'Active',
        ]);

        $liamRecord = ChildRecord::create([
            'patient_id' => $liamPatient->id,
            'mother_id' => $elena->id,
            'birth_weight_kg' => 3.2,
            'birth_height_cm' => 50.0,
            'head_circumference_cm' => 34.0,
            'birth_type' => 'Single',
            'delivery_type' => 'Normal',
            'delivery_place' => 'Barangay Bicao Health Station',
            'attendant' => 'Midwife Elena',
            'birth_order' => '1st',
            'blood_type' => 'O+',
            'has_newborn_screening' => true,
            'has_hearing_screening' => true,
            'has_eye_prophylaxis' => true,
            'has_vitamin_k' => true,
            'has_bcg_at_birth' => true,
            'has_hepb_at_birth' => true,
        ]);

        // Seed Liam's Immunizations
        // BCG at birth
        Immunization::create([
            'child_record_id' => $liamRecord->id,
            'vaccine_name' => 'BCG',
            'dose_number' => 1,
            'scheduled_date' => '2025-10-05',
            'given_date' => '2025-10-05',
            'administered_by' => 'Midwife Elena',
            'remarks' => 'Given at birth',
            'status' => 'Given',
        ]);
        // HepB at birth
        Immunization::create([
            'child_record_id' => $liamRecord->id,
            'vaccine_name' => 'Hepatitis B',
            'dose_number' => 1,
            'scheduled_date' => '2025-10-05',
            'given_date' => '2025-10-05',
            'administered_by' => 'Midwife Elena',
            'remarks' => 'Given at birth',
            'status' => 'Given',
        ]);
        // DPT-HepB-Hib 1,2,3
        foreach ([1 => '2025-11-20', 2 => '2025-12-20', 3 => '2026-01-20'] as $dose => $date) {
            Immunization::create([
                'child_record_id' => $liamRecord->id,
                'vaccine_name' => 'Pentavalent (DPT-HepB-Hib)',
                'dose_number' => $dose,
                'scheduled_date' => $date,
                'given_date' => $date,
                'administered_by' => 'Midwife Elena',
                'status' => 'Given',
            ]);
        }
        // OPV 1,2,3
        foreach ([1 => '2025-11-20', 2 => '2025-12-20', 3 => '2026-01-20'] as $dose => $date) {
            Immunization::create([
                'child_record_id' => $liamRecord->id,
                'vaccine_name' => 'OPV',
                'dose_number' => $dose,
                'scheduled_date' => $date,
                'given_date' => $date,
                'administered_by' => 'Midwife Elena',
                'status' => 'Given',
            ]);
        }
        // IPV
        Immunization::create([
            'child_record_id' => $liamRecord->id,
            'vaccine_name' => 'IPV',
            'dose_number' => 1,
            'scheduled_date' => '2026-01-20',
            'given_date' => '2026-01-20',
            'administered_by' => 'Midwife Elena',
            'status' => 'Given',
        ]);
        // PCV 1,2,3
        foreach ([1 => '2025-11-20', 2 => '2025-12-20', 3 => '2026-01-20'] as $dose => $date) {
            Immunization::create([
                'child_record_id' => $liamRecord->id,
                'vaccine_name' => 'PCV',
                'dose_number' => $dose,
                'scheduled_date' => $date,
                'given_date' => $date,
                'administered_by' => 'Midwife Elena',
                'status' => 'Given',
            ]);
        }
        // MMR 1 (at 9 months - scheduled/overdue)
        Immunization::create([
            'child_record_id' => $liamRecord->id,
            'vaccine_name' => 'MMR',
            'dose_number' => 1,
            'scheduled_date' => '2026-07-05',
            'given_date' => null,
            'status' => 'Scheduled',
        ]);

        // Seed Liam's Growth Measurements
        GrowthMeasurement::create(['child_record_id' => $liamRecord->id, 'date' => '2025-10-05', 'age_months' => 0, 'weight_kg' => 3.2, 'height_cm' => 50.0, 'status' => 'Normal']);
        GrowthMeasurement::create(['child_record_id' => $liamRecord->id, 'date' => '2025-12-05', 'age_months' => 2, 'weight_kg' => 5.4, 'height_cm' => 57.0, 'status' => 'Normal']);
        GrowthMeasurement::create(['child_record_id' => $liamRecord->id, 'date' => '2026-02-05', 'age_months' => 4, 'weight_kg' => 6.8, 'height_cm' => 62.0, 'status' => 'Normal']);
        GrowthMeasurement::create(['child_record_id' => $liamRecord->id, 'date' => '2026-04-05', 'age_months' => 6, 'weight_kg' => 7.9, 'height_cm' => 66.0, 'status' => 'Normal']);
        GrowthMeasurement::create(['child_record_id' => $liamRecord->id, 'date' => '2026-07-05', 'age_months' => 9, 'weight_kg' => 8.8, 'height_cm' => 70.0, 'status' => 'Normal']);

        // Child 2: Sofia Garcia (2 months old)
        $sofiaPatient = Patient::create([
            'first_name' => 'Sofia',
            'last_name' => 'Garcia',
            'dob' => '2026-05-02',
            'gender' => 'Female',
            'phone' => '09171234567',
            'email' => null,
            'address' => 'Purok 2, Barangay Bicao',
            'barangay' => 'Bicao',
            'registration_type' => 'Child',
            'status' => 'Due for Visit',
        ]);

        $sofiaRecord = ChildRecord::create([
            'patient_id' => $sofiaPatient->id,
            'mother_id' => $elena->id,
            'birth_weight_kg' => 2.9,
            'birth_height_cm' => 48.0,
            'head_circumference_cm' => 33.0,
            'birth_type' => 'Single',
            'delivery_type' => 'Normal',
            'delivery_place' => 'Barangay Bicao Health Station',
            'attendant' => 'Midwife Elena',
            'birth_order' => '2nd',
            'blood_type' => 'O+',
            'has_newborn_screening' => true,
            'has_hearing_screening' => true,
            'has_eye_prophylaxis' => true,
            'has_vitamin_k' => true,
            'has_bcg_at_birth' => true,
            'has_hepb_at_birth' => true,
        ]);

        // Seed Sofia's Immunizations
        Immunization::create([
            'child_record_id' => $sofiaRecord->id,
            'vaccine_name' => 'BCG',
            'dose_number' => 1,
            'scheduled_date' => '2026-05-02',
            'given_date' => '2026-05-02',
            'administered_by' => 'Midwife Elena',
            'status' => 'Given',
        ]);
        Immunization::create([
            'child_record_id' => $sofiaRecord->id,
            'vaccine_name' => 'Hepatitis B',
            'dose_number' => 1,
            'scheduled_date' => '2026-05-02',
            'given_date' => '2026-05-02',
            'administered_by' => 'Midwife Elena',
            'status' => 'Given',
        ]);
        // Pentavalent 1, OPV 1, PCV 1 given at 1.5 months
        Immunization::create([
            'child_record_id' => $sofiaRecord->id,
            'vaccine_name' => 'Pentavalent (DPT-HepB-Hib)',
            'dose_number' => 1,
            'scheduled_date' => '2026-06-17',
            'given_date' => '2026-06-17',
            'administered_by' => 'Midwife Elena',
            'status' => 'Given',
        ]);
        Immunization::create([
            'child_record_id' => $sofiaRecord->id,
            'vaccine_name' => 'OPV',
            'dose_number' => 1,
            'scheduled_date' => '2026-06-17',
            'given_date' => '2026-06-17',
            'administered_by' => 'Midwife Elena',
            'status' => 'Given',
        ]);
        Immunization::create([
            'child_record_id' => $sofiaRecord->id,
            'vaccine_name' => 'PCV',
            'dose_number' => 1,
            'scheduled_date' => '2026-06-17',
            'given_date' => '2026-06-17',
            'administered_by' => 'Midwife Elena',
            'status' => 'Given',
        ]);

        // Scheduled Pentavalent 2 (due now at 2.5 months)
        Immunization::create([
            'child_record_id' => $sofiaRecord->id,
            'vaccine_name' => 'Pentavalent (DPT-HepB-Hib)',
            'dose_number' => 2,
            'scheduled_date' => '2026-07-17',
            'status' => 'Scheduled',
        ]);
        Immunization::create([
            'child_record_id' => $sofiaRecord->id,
            'vaccine_name' => 'OPV',
            'dose_number' => 2,
            'scheduled_date' => '2026-07-17',
            'status' => 'Scheduled',
        ]);

        // Sofia's Growth
        GrowthMeasurement::create(['child_record_id' => $sofiaRecord->id, 'date' => '2026-05-02', 'age_months' => 0, 'weight_kg' => 2.9, 'height_cm' => 48.0, 'status' => 'Normal']);
        GrowthMeasurement::create(['child_record_id' => $sofiaRecord->id, 'date' => '2026-06-02', 'age_months' => 1, 'weight_kg' => 4.1, 'height_cm' => 52.0, 'status' => 'Normal']);
        GrowthMeasurement::create(['child_record_id' => $sofiaRecord->id, 'date' => '2026-07-02', 'age_months' => 2, 'weight_kg' => 5.2, 'height_cm' => 56.5, 'status' => 'Normal']);

        // 4. Create another patient: Maria Santos-Dizon (High Risk prenatal patient)
        $maria = Patient::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos-Dizon',
            'dob' => '1995-02-15',
            'gender' => 'Female',
            'phone' => '09159998888',
            'email' => 'maria@example.com',
            'address' => 'Purok 4, Barangay Bicao',
            'barangay' => 'Bicao',
            'occupation' => 'Teacher',
            'emergency_contact_name' => 'Pedro Dizon',
            'emergency_contact_phone' => '09161112222',
            'registration_type' => 'Maternal',
            'status' => 'High Risk',
        ]);

        $mariaMaternal = MaternalRecord::create([
            'patient_id' => $maria->id,
            'lmp' => '2026-01-20',
            'edd' => '2026-10-27',
            'gravida' => 2,
            'para' => 1,
            'abortions' => 0,
            'still_births' => 0,
            'philhealth_number' => '24-112233445-6',
            'blood_type' => 'A+',
            'height_cm' => 150.0,
            'allergies' => 'Penicillin',
            'medical_history' => [
                'Hypertension' => true,
                'Diabetes' => false,
                'Asthma' => true,
                'Heart Disease' => false,
                'Anemia' => true,
                'Multiple Births' => false
            ],
            'birth_plan' => [
                'facility' => 'Carmen District Hospital',
                'attendant' => 'Dr. Elena',
                'helper' => 'Pedro Dizon',
                'phone' => '09161112222',
                'transport' => 'Ambulance / Brgy Service',
                'blood_donor' => 'BHW Maria',
            ],
        ]);

        // Seed checkups for Maria (some at risk)
        MaternalCheckup::create([
            'maternal_record_id' => $mariaMaternal->id,
            'visit_number' => 1,
            'date' => '2026-03-20',
            'weight_kg' => 60.5,
            'bp' => '130/85',
            'age_of_gestation' => '8w 3d',
            'fetal_heart_rate' => 142,
            'attendant' => 'Dr. Elena',
            'status' => 'Screening',
            'notes' => 'Borderline high BP. Advised low salt diet.',
            'next_visit_date' => '2026-04-20',
        ]);

        MaternalCheckup::create([
            'maternal_record_id' => $mariaMaternal->id,
            'visit_number' => 2,
            'date' => '2026-04-20',
            'weight_kg' => 63.2,
            'bp' => '135/90',
            'age_of_gestation' => '12w 6d',
            'fetal_heart_rate' => 145,
            'attendant' => 'Dr. Elena',
            'status' => 'At Risk',
            'notes' => 'Gestational hypertension. Prescribed Methyldopa. Scheduled for follow-up.',
            'next_visit_date' => '2026-05-25',
        ]);

        // 5. Seed chat messages for the patient portal communication
        ChatMessage::create([
            'sender_id' => $elenaUser->id,
            'receiver_id' => $admin->id,
            'message' => 'Good afternoon po. Itatanong ko lang po sana kung kailangan bang gutom sa laboratory exam bukas?',
            'is_read' => true,
            'created_at' => Carbon::now()->subHours(5),
        ]);

        ChatMessage::create([
            'sender_id' => $admin->id,
            'receiver_id' => $elenaUser->id,
            'message' => 'Opo, Nanay Elena. Kailangan po na walang kain o inum (fasting) ng 8 oras bago kunan ng dugo para sa OGTT (glucose test).',
            'is_read' => false,
            'created_at' => Carbon::now()->subHours(4),
        ]);

        ChatMessage::create([
            'sender_id' => $elenaUser->id,
            'receiver_id' => $admin->id,
            'message' => 'Sige po midwife. Maraming salamat po sa pagsagot!',
            'is_read' => false,
            'created_at' => Carbon::now()->subHours(3),
        ]);
    }
}
