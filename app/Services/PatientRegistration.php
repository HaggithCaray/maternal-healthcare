<?php

namespace App\Services;

use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Creates a patient with their maternal or child record, used by the registration form and offline sync.
 * Only records what was entered: nothing is assumed about the birth plan, birth measurements or birth doses.
 */
class PatientRegistration
{
    /**
     * Philippine EPI schedule: [vaccine, dose, weeks after birth].
     */
    public const SCHEDULE = [
        ['BCG', 1, 0],
        ['Hepatitis B', 1, 0],
        ['Pentavalent (DPT-HepB-Hib)', 1, 6],
        ['Pentavalent (DPT-HepB-Hib)', 2, 10],
        ['Pentavalent (DPT-HepB-Hib)', 3, 14],
        ['OPV', 1, 6],
        ['OPV', 2, 10],
        ['OPV', 3, 14],
        ['IPV', 1, 14],
        ['PCV', 1, 6],
        ['PCV', 2, 10],
        ['PCV', 3, 14],
        ['MMR', 1, 39],
        ['MMR', 2, 52],
    ];

    public const BIRTH_TYPES = ['Single', 'Twin', 'Triplet', 'Multiple'];

    public const DELIVERY_TYPES = ['Normal', 'C-Section', 'Assisted'];

    /**
     * Validation rules shared by the registration form and offline sync.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date|before_or_equal:today',
            'gender' => 'required|in:Female,Male',
            ...self::contactRules(),
            'email' => ['nullable', 'email', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
                // Otherwise the patient record would be linked to (and log in as) a staff user.
                if (User::where('email', $value)->where('role', '!=', 'user')->exists()) {
                    $fail('This email belongs to a staff account and cannot be used for a patient.');
                }
            }],
            'barangay' => 'nullable|string|max:100',
            'occupation' => 'nullable|string|max:255',
            'registration_type' => 'required|in:Maternal,Child',

            // Maternal
            'lmp' => 'nullable|date|before_or_equal:today',
            'gravida' => 'nullable|integer|min:0|max:30',
            'para' => 'nullable|integer|min:0|max:30',
            'philhealth_number' => 'nullable|string|max:50',
            'medical_history' => 'nullable|array',
            'allergies' => 'nullable|string|max:1000',
            'birth_plan_facility' => 'nullable|string|max:255',
            'birth_plan_attendant' => 'nullable|string|max:255',

            // Child
            'mother_id' => ['nullable', Rule::exists('patients', 'id')->where('registration_type', 'Maternal')],
            'birth_weight_kg' => 'nullable|numeric|min:0.3|max:7',
            'birth_height_cm' => 'nullable|numeric|min:20|max:70',
            'birth_type' => ['nullable', Rule::in(self::BIRTH_TYPES)],
            'delivery_type' => ['nullable', Rule::in(self::DELIVERY_TYPES)],
            'has_bcg_at_birth' => 'nullable|boolean',
            'has_hepb_at_birth' => 'nullable|boolean',
        ];
    }

    /**
     * Phone, address and emergency contact. A mother must give them. A child linked to a registered
     * mother uses hers (Patient::contactMother), so they are optional; for a child without a linked
     * mother they are the parent or guardian's (name, phone and address required).
     *
     * @param  bool|null  $child  whether the patient is a child; null when the submitted
     *                            registration_type decides (registration form and offline sync)
     * @return array<string, list<string>>
     */
    public static function contactRules(?bool $child = null): array
    {
        $required = match ($child) {
            null => ['required_if:registration_type,Maternal', 'required_without:mother_id'],
            true => ['required_without:mother_id'],
            false => ['required'],
        };
        $requiredForMother = match ($child) {
            null => ['required_if:registration_type,Maternal'],
            true => [],
            false => ['required'],
        };

        return [
            'phone' => ['nullable', 'string', 'max:30', ...$required],
            'address' => ['nullable', 'string', 'max:500', ...$required],
            'emergency_contact_name' => ['nullable', 'string', 'max:255', ...$required],
            'emergency_contact_phone' => ['nullable', 'string', 'max:30', ...$requiredForMother],
        ];
    }

    /**
     * Register a validated patient.
     *
     * @return array{patient: Patient, temporaryPassword: ?string} temporaryPassword is set when a portal login was created
     */
    public function register(array $data): array
    {
        // A child never gets a login of its own: the mother sees the child in her portal through
        // the child record's mother link. An email entered for a child is not kept.
        if ($data['registration_type'] === 'Child') {
            $data['email'] = null;
            $data['occupation'] = null;

            // Linked to a registered mother: her current contact details are shown, so keep no copy.
            if (! empty($data['mother_id'])) {
                foreach (Patient::CONTACT_FIELDS as $field) {
                    $data[$field] = null;
                }
            }
        }

        return DB::transaction(function () use ($data) {
            [$userId, $temporaryPassword] = $this->portalAccount($data);

            $patient = Patient::create([
                'user_id' => $userId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'dob' => $data['dob'],
                'gender' => $data['gender'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'barangay' => $data['barangay'] ?? 'Bicao',
                'occupation' => $data['occupation'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'registration_type' => $data['registration_type'],
                'status' => 'Active',
            ]);

            $data['registration_type'] === 'Maternal'
                ? $this->createMaternalRecord($patient, $data)
                : $this->createChildRecord($patient, $data);

            return ['patient' => $patient, 'temporaryPassword' => $temporaryPassword];
        });
    }

    /**
     * Link an existing patient login with this email, or create one with a temporary password.
     *
     * @return array{0: ?int, 1: ?string} [user id, temporary password if created]
     */
    protected function portalAccount(array $data): array
    {
        $email = $data['email'] ?? null;
        if (! $email) {
            return [null, null];
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            return [$user->id, null];
        }

        $temporaryPassword = User::temporaryPassword();
        $user = User::create([
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'email' => $email,
            'password' => Hash::make($temporaryPassword),
            'role' => 'user',
        ]);

        return [$user->id, $temporaryPassword];
    }

    protected function createMaternalRecord(Patient $patient, array $data): void
    {
        $lmp = ! empty($data['lmp']) ? Carbon::parse($data['lmp']) : null;
        $birthPlan = array_filter([
            'facility' => $data['birth_plan_facility'] ?? null,
            'attendant' => $data['birth_plan_attendant'] ?? null,
        ]);

        MaternalRecord::create([
            'patient_id' => $patient->id,
            'lmp' => $lmp,
            'edd' => $lmp?->copy()->addDays(280),
            'gravida' => $data['gravida'] ?? 1,
            'para' => $data['para'] ?? 0,
            'philhealth_number' => $data['philhealth_number'] ?? null,
            'medical_history' => MaternalRecord::normalizeConditions($data['medical_history'] ?? []),
            'allergies' => $data['allergies'] ?? null,
            'birth_plan' => $birthPlan ?: null,
        ]);
    }

    protected function createChildRecord(Patient $patient, array $data): void
    {
        $dob = Carbon::parse($data['dob']);
        $weight = $data['birth_weight_kg'] ?? null;
        $height = $data['birth_height_cm'] ?? null;

        $child = ChildRecord::create([
            'patient_id' => $patient->id,
            'mother_id' => $data['mother_id'] ?? null,
            'birth_weight_kg' => $weight,
            'birth_height_cm' => $height,
            'birth_type' => $data['birth_type'] ?? 'Single',
            'delivery_type' => $data['delivery_type'] ?? 'Normal',
            'has_bcg_at_birth' => filter_var($data['has_bcg_at_birth'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'has_hepb_at_birth' => filter_var($data['has_hepb_at_birth'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'allergies' => $data['allergies'] ?? null,
        ]);

        // A birth measurement only when both values were actually recorded.
        if ($weight !== null && $height !== null) {
            GrowthMeasurement::create([
                'child_record_id' => $child->id,
                'date' => $dob->format('Y-m-d'),
                'age_months' => 0,
                'weight_kg' => $weight,
                'height_cm' => $height,
            ]);
        }

        foreach (self::SCHEDULE as [$vaccine, $dose, $weeks]) {
            Immunization::create([
                'child_record_id' => $child->id,
                'vaccine_name' => $vaccine,
                'dose_number' => $dose,
                'scheduled_date' => $dob->copy()->addWeeks($weeks)->format('Y-m-d'),
                'status' => 'Scheduled',
            ]);
        }

        // Birth doses count as given only when the form says so; otherwise they stay due.
        $child->syncBirthDoses();
    }
}
