<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\SyncReceipt;
use App\Services\PatientRegistration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
     * Accept queued offline items and apply each one atomically, at most once.
     *
     * Request body: { "items": [ { "id": 1, "uuid": "…", "type": "patient_registration", "data": { ... } } ] }
     * Items already applied (a retry after a lost response, or two syncs running at once) are
     * skipped but still reported in synced_ids, so the device removes them from its outbox.
     */
    public function registrations(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'items' => 'required|array|max:500',
        ]);

        $syncedIds = [];
        $duplicates = 0;
        $errors = [];
        $rejected = [];

        foreach ($request->input('items') as $item) {
            $item = is_array($item) ? $item : [];
            $clientId = $item['id'] ?? null;
            $type = $item['type'] ?? null;
            $data = is_array($item['data'] ?? null) ? $item['data'] : [];
            $key = SyncReceipt::keyFor($item);

            if (SyncReceipt::where('key', $key)->exists()) {
                $duplicates++;
                if ($clientId !== null) {
                    $syncedIds[] = $clientId;
                }
                continue;
            }

            try {
                DB::transaction(function () use ($type, $data, $key) {
                    // Claim the item first: a concurrent sync of the same item fails here on the unique key.
                    SyncReceipt::create(['key' => $key, 'type' => (string) $type, 'user_id' => auth()->id()]);

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
            } catch (UniqueConstraintViolationException $e) {
                // Another request applied this item while we were working on it.
                if (SyncReceipt::where('key', $key)->exists()) {
                    $duplicates++;
                    if ($clientId !== null) {
                        $syncedIds[] = $clientId;
                    }
                } else {
                    $this->reject($errors, $rejected, $clientId, $type, $this->reason($e));
                }
            } catch (\Throwable $e) {
                $this->reject($errors, $rejected, $clientId, $type, $this->reason($e));
            }
        }

        AuditLog::log('offline_batch_sync', null, [
            'total_items' => count($request->input('items')),
            'synced_count' => count($syncedIds),
            'duplicates_skipped' => $duplicates,
            'errors_count' => count($errors),
        ]);

        return response()->json([
            'success' => empty($errors),
            'synced' => count($syncedIds),
            'synced_ids' => $syncedIds,
            'duplicates' => $duplicates,
            'errors' => $errors,
            // Per item, so the device can show why each entry is still waiting.
            'rejected' => $rejected,
        ]);
    }

    private function reject(array &$errors, array &$rejected, mixed $clientId, mixed $type, string $reason): void
    {
        $errors[] = "Item {$clientId} [{$type}]: {$reason}";
        $rejected[] = ['id' => $clientId, 'type' => $type, 'reason' => $reason];
    }

    /**
     * A reason the person who made the entry can act on; server faults are logged, not shown.
     */
    private function reason(\Throwable $e): string
    {
        if ($e instanceof ModelNotFoundException) {
            return 'The patient record this entry belongs to no longer exists.';
        }

        if ($e instanceof \RuntimeException && ! $e instanceof \Illuminate\Database\QueryException) {
            return $e->getMessage();
        }

        report($e);

        return 'It could not be saved because of a server error. Please try again later.';
    }

    /**
     * Validate one queued item; failures are reported per item in the sync response.
     */
    private function validateItem(array $data, array $rules): void
    {
        $validator = Validator::make($data, $rules, [
            // Shown to the midwife on the device, so say what happened rather than which id failed.
            'maternal_record_id.exists' => "The mother's prenatal record no longer exists.",
            'child_record_id.exists' => "The child's record no longer exists.",
            'immunization_id.exists' => 'This vaccine dose no longer exists on the server.',
        ]);

        if ($validator->fails()) {
            throw new \RuntimeException(
                implode(' ', collect($validator->errors()->all())->take(3)->all())
            );
        }
    }

    /**
     * The day an offline entry was made. Devices send the moment of entry (recorded_at, an ISO 8601
     * instant), which is dated in the app's timezone exactly as an online entry made then would be.
     */
    private function entryDate(array $data, string $dateField): string
    {
        if (!empty($data['recorded_at'])) {
            return Carbon::parse($data['recorded_at'])->setTimezone(config('app.timezone'))->toDateString();
        }

        return $data[$dateField] ?? Carbon::now()->format('Y-m-d');
    }

    /**
     * Create a patient from offline registration data (same rules and records as the web form).
     */
    private function createPatient(array $data): void
    {
        $this->validateItem($data, PatientRegistration::rules());

        $patient = app(PatientRegistration::class)->register($data)['patient'];

        AuditLog::log('create_patient', $patient, [
            'registration_type' => $patient->registration_type,
            'source' => 'offline_sync',
        ]);
    }

    /**
     * Create a checkup entry from offline data.
     */
    private function createCheckup(array $data): void
    {
        $this->validateItem($data, [
            'maternal_record_id' => 'required|exists:maternal_records,id',
            'recorded_at' => 'nullable|date|before:tomorrow',
            'date' => 'nullable|date|before_or_equal:today',
            'weight_kg' => 'required|numeric|min:25|max:250',
            'bp' => ['required', 'string', 'regex:/^\s*\d{2,3}\s*\/\s*\d{2,3}\s*$/'],
            'fetal_heart_rate' => 'nullable|integer|min:50|max:250',
            'notes' => 'nullable|string|max:5000',
        ]);

        $record = MaternalRecord::findOrFail($data['maternal_record_id']);
        $visitNumber = $record->checkups()->count() + 1;

        // Status, risk flags and next visit are derived by MaternalCheckup on save; age of gestation
        // too when an LMP is on file (otherwise a field estimate such as "24w 2d" is kept).
        $checkup = MaternalCheckup::create([
            'maternal_record_id' => $record->id,
            'visit_number' => $visitNumber,
            'date' => $this->entryDate($data, 'date'),
            'weight_kg' => $data['weight_kg'],
            'bp' => preg_replace('/\s+/', '', $data['bp']),
            'age_of_gestation' => $data['age_of_gestation'] ?? null,
            'fetal_heart_rate' => $data['fetal_heart_rate'] ?? null,
            'attendant' => auth()->user()->name,
            'notes' => $data['notes'] ?? null,
            'next_visit_date' => $data['next_visit_date'] ?? null,
        ]);

        AuditLog::log('create_maternal_checkup', $checkup, [
            'patient_id' => $record->patient_id,
            'visit_number' => $visitNumber,
            'status' => $checkup->status,
            'source' => 'offline_sync',
        ]);
    }

    /**
     * Create a growth measurement from offline data.
     */
    private function createGrowth(array $data): void
    {
        $this->validateItem($data, [
            'child_record_id' => 'required|exists:child_records,id',
            'recorded_at' => 'nullable|date|before:tomorrow',
            'date' => 'nullable|date|before_or_equal:today',
            'weight_kg' => 'required|numeric|min:0.5|max:100',
            'height_cm' => 'required|numeric|min:20|max:200',
        ]);

        $childRecord = ChildRecord::findOrFail($data['child_record_id']);

        // Age in months, WHO z-scores and status are derived by GrowthMeasurement on save.
        $measurement = GrowthMeasurement::create([
            'child_record_id' => $childRecord->id,
            'date' => $this->entryDate($data, 'date'),
            'age_months' => $data['age_months'] ?? 0,
            'weight_kg' => $data['weight_kg'],
            'height_cm' => $data['height_cm'],
        ]);

        AuditLog::log('create_growth_measurement', $measurement, [
            'child_record_id' => $childRecord->id,
            'status' => $measurement->status,
            'source' => 'offline_sync',
        ]);
    }

    /**
     * Mark a vaccine dose as given from offline data.
     */
    private function updateImmunization(array $data): void
    {
        $this->validateItem($data, [
            'immunization_id' => 'required|exists:immunizations,id',
            'recorded_at' => 'nullable|date|before:tomorrow',
            'given_date' => 'nullable|date|before_or_equal:today',
            'remarks' => 'nullable|string|max:500',
        ]);

        $immunization = Immunization::findOrFail($data['immunization_id']);

        // Already recorded (online, or by another device while this one was offline): keep that record.
        if ($immunization->status === 'Given') {
            return;
        }

        $immunization->update([
            'status' => 'Given',
            'given_date' => $this->entryDate($data, 'given_date'),
            'administered_by' => auth()->user()->name,
            'remarks' => $data['remarks'] ?? 'Administered offline during field visit',
        ]);

        AuditLog::log('administer_vaccine', $immunization, [
            'vaccine' => $immunization->vaccine_name,
            'dose' => $immunization->dose_number,
            'source' => 'offline_sync',
        ]);
    }
}
