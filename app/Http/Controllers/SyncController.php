<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SyncController extends Controller
{
    /**
     * Return a fresh CSRF token for the frontend sync flow.
     */
    public function token(Request $request)
    {
        return response()->json(['token' => csrf_token()]);
    }

    /**
     * Accept queued offline items and apply them to the database atomically.
     *
     * Request body: { "items": [ { "id": 1, "type": "patient_registration", "data": { ... } } ] }
     */
    public function registrations(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'items' => 'required|array',
        ]);

        $syncedIds = [];
        $errors = [];

        foreach ($request->input('items') as $item) {
            $clientId = $item['id'] ?? null;
            $type = $item['type'] ?? null;
            $data = $item['data'] ?? [];

            try {
                DB::transaction(function () use ($type, $data) {
                    match ($type) {
                        'patient_registration' => $this->createPatient($data),
                        'maternal_checkup' => $this->createCheckup($data),
                        'child_growth' => $this->createGrowth($data),
                        'immunization_update' => $this->updateImmunization($data),
                        default => throw new \RuntimeException("Unknown item type: {$type}"),
                    };
                });

                if ($clientId !== null) {
                    $syncedIds[] = $clientId;
                }
            } catch (\Throwable $e) {
                $errors[] = "Item {$clientId} [{$type}]: " . $e->getMessage();
            }
        }

        AuditLog::log('offline_batch_sync', null, [
            'total_items' => count($request->input('items')),
            'synced_count' => count($syncedIds),
            'errors_count' => count($errors),
        ]);

        return response()->json([
            'success' => empty($errors),
            'synced' => count($syncedIds),
            'synced_ids' => $syncedIds,
            'errors' => $errors,
        ]);
    }

    /**
     * Validate one queued item; failures are reported per item in the sync response.
     */
    private function validateItem(array $data, array $rules): void
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \RuntimeException(
                implode(' ', collect($validator->errors()->all())->take(3)->all())
            );
        }
    }

    /**
     * Create a patient from offline registration data.
     */
    private function createPatient(array $data): void
    {
        $this->validateItem($data, [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|string',
            'phone' => 'required|string',
            'email' => 'nullable|email',
            'address' => 'required|string',
            'emergency_contact_name' => 'required|string',
            'emergency_contact_phone' => 'required|string',
            'registration_type' => 'required|in:Maternal,Child',
            'mother_id' => ['nullable', Rule::exists('patients', 'id')->where('registration_type', 'Maternal')],
        ]);

        $userId = null;
        $email = $data['email'] ?? null;
        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user && ! $user->isUser()) {
                throw new \RuntimeException('This email belongs to a staff account and cannot be used for a patient.');
            }
            if (!$user) {
                $user = User::create([
                    'name' => $data['first_name'] . ' ' . $data['last_name'],
                    'email' => $email,
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'user',
                ]);
            }
            $userId = $user->id;
        }

        $patient = Patient::create([
            'user_id' => $userId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'dob' => $data['dob'],
            'gender' => $data['gender'],
            'phone' => $data['phone'],
            'email' => $email,
            'address' => $data['address'],
            'barangay' => $data['barangay'] ?? 'Bicao',
            'occupation' => $data['occupation'] ?? null,
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_phone' => $data['emergency_contact_phone'],
            'registration_type' => $data['registration_type'],
            'status' => 'Active',
        ]);

        $dob = Carbon::parse($data['dob']);

        if ($data['registration_type'] === 'Maternal') {
            $lmp = !empty($data['lmp']) ? Carbon::parse($data['lmp']) : null;
            $edd = $lmp ? $lmp->copy()->addDays(280) : null;

            MaternalRecord::create([
                'patient_id' => $patient->id,
                'lmp' => $lmp,
                'edd' => $edd,
                'gravida' => $data['gravida'] ?? 1,
                'para' => $data['para'] ?? 0,
                'philhealth_number' => $data['philhealth_number'] ?? null,
                'medical_history' => MaternalRecord::normalizeConditions($data['medical_history'] ?? []),
                'allergies' => $data['allergies'] ?? null,
                'birth_plan' => [
                    'facility' => 'Barangay Bicao Health Station',
                    'attendant' => 'Midwife Elena',
                ],
            ]);
        } else {
            $child = ChildRecord::create([
                'patient_id' => $patient->id,
                'mother_id' => $data['mother_id'] ?? null,
                'birth_weight_kg' => $data['birth_weight_kg'] ?? 3.0,
                'birth_height_cm' => $data['birth_height_cm'] ?? 50.0,
                'birth_type' => 'Single',
                'delivery_type' => 'Normal',
            ]);

            GrowthMeasurement::create([
                'child_record_id' => $child->id,
                'date' => $dob->format('Y-m-d'),
                'age_months' => 0,
                'weight_kg' => $data['birth_weight_kg'] ?? 3.0,
                'height_cm' => $data['birth_height_cm'] ?? 50.0,
            ]);

            $schedule = [
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

            foreach ($schedule as $vaccine) {
                Immunization::create([
                    'child_record_id' => $child->id,
                    'vaccine_name' => $vaccine[0],
                    'dose_number' => $vaccine[1],
                    'scheduled_date' => $dob->copy()->addWeeks($vaccine[2])->format('Y-m-d'),
                    'status' => $vaccine[2] === 0 ? 'Given' : 'Scheduled',
                    'given_date' => $vaccine[2] === 0 ? $dob->format('Y-m-d') : null,
                    'administered_by' => $vaccine[2] === 0 ? 'Midwife Elena' : null,
                ]);
            }
        }
    }

    /**
     * Create a checkup entry from offline data.
     */
    private function createCheckup(array $data): void
    {
        $this->validateItem($data, [
            'maternal_record_id' => 'required|exists:maternal_records,id',
            'date' => 'nullable|date|before_or_equal:today',
            'weight_kg' => 'required|numeric|min:25|max:250',
            'bp' => ['required', 'string', 'regex:/^\s*\d{2,3}\s*\/\s*\d{2,3}\s*$/'],
            'fetal_heart_rate' => 'nullable|integer|min:50|max:250',
        ]);

        $record = MaternalRecord::findOrFail($data['maternal_record_id']);
        $visitNumber = $record->checkups()->count() + 1;

        // Status, risk flags and next visit are derived by MaternalCheckup on save; age of gestation
        // too when an LMP is on file (otherwise a field estimate such as "24w 2d" is kept).
        MaternalCheckup::create([
            'maternal_record_id' => $record->id,
            'visit_number' => $visitNumber,
            'date' => $data['date'] ?? Carbon::now()->format('Y-m-d'),
            'weight_kg' => $data['weight_kg'],
            'bp' => preg_replace('/\s+/', '', $data['bp']),
            'age_of_gestation' => $data['age_of_gestation'] ?? null,
            'fetal_heart_rate' => $data['fetal_heart_rate'] ?? null,
            'attendant' => auth()->user()->name,
            'notes' => $data['notes'] ?? null,
            'next_visit_date' => $data['next_visit_date'] ?? null,
        ]);
    }

    /**
     * Create a growth measurement from offline data.
     */
    private function createGrowth(array $data): void
    {
        $this->validateItem($data, [
            'child_record_id' => 'required|exists:child_records,id',
            'date' => 'nullable|date|before_or_equal:today',
            'weight_kg' => 'required|numeric|min:0.5|max:100',
            'height_cm' => 'required|numeric|min:20|max:200',
        ]);

        $childRecord = ChildRecord::findOrFail($data['child_record_id']);

        // Age in months, WHO z-scores and status are derived by GrowthMeasurement on save.
        GrowthMeasurement::create([
            'child_record_id' => $childRecord->id,
            'date' => $data['date'] ?? Carbon::now()->format('Y-m-d'),
            'age_months' => $data['age_months'] ?? 0,
            'weight_kg' => $data['weight_kg'],
            'height_cm' => $data['height_cm'],
        ]);
    }

    /**
     * Update an immunization record from offline data.
     */
    private function updateImmunization(array $data): void
    {
        $immunization = Immunization::findOrFail($data['immunization_id']);
        $immunization->update([
            'status' => 'Given',
            'given_date' => $data['given_date'] ?? Carbon::now()->format('Y-m-d'),
            'administered_by' => auth()->user()->name,
            'remarks' => $data['remarks'] ?? 'Administered offline during field visit',
        ]);
    }
}
