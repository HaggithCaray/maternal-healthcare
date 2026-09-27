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
                    $errors[] = "Item {$clientId} [{$type}]: " . $e->getMessage();
                }
            } catch (\Throwable $e) {
                $errors[] = "Item {$clientId} [{$type}]: " . $e->getMessage();
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
     * Create a patient from offline registration data (same rules and records as the web form).
     */
    private function createPatient(array $data): void
    {
        $this->validateItem($data, PatientRegistration::rules());

        app(PatientRegistration::class)->register($data);
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
