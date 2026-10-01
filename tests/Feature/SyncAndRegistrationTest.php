<?php

namespace Tests\Feature;

use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\SyncReceipt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SyncAndRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-20 10:00:00');

        $this->adminUser = User::create([
            'name' => 'Midwife Rosa',
            'email' => 'rosa@health.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);
    }

    protected function patientData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'dob' => '1998-04-12',
            'gender' => 'Female',
            'phone' => '09123456789',
            'address' => 'Purok 2',
            'emergency_contact_name' => 'Pedro Reyes',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Maternal',
        ], $overrides);
    }

    protected function childData(array $overrides = []): array
    {
        return $this->patientData(array_merge([
            'first_name' => 'Nico',
            'dob' => '2026-08-01',
            'gender' => 'Male',
            'registration_type' => 'Child',
        ], $overrides));
    }

    protected function sync(array $items)
    {
        return $this->actingAs($this->adminUser)->postJson('/api/sync/batch', ['items' => $items]);
    }

    // --- #11 Offline sync applies each item at most once ------------------------------

    public function test_retrying_an_item_with_the_same_uuid_does_not_duplicate_it(): void
    {
        $item = ['id' => 7, 'uuid' => '6f1c2a0e-3b8d-4c5e-9f7a-1d2e3f4a5b6c', 'type' => 'patient_registration', 'data' => $this->patientData()];

        $this->sync([$item])->assertJson(['synced_ids' => [7], 'duplicates' => 0]);
        // The response was "lost", so the device sends the same item again.
        $this->sync([$item])->assertJson(['success' => true, 'synced_ids' => [7], 'duplicates' => 1]);

        $this->assertSame(1, Patient::count());
        $this->assertDatabaseHas('sync_receipts', [
            'key' => '6f1c2a0e-3b8d-4c5e-9f7a-1d2e3f4a5b6c',
            'type' => 'patient_registration',
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_items_queued_by_older_clients_without_uuid_are_deduplicated_by_content(): void
    {
        $item = ['id' => 3, 'type' => 'patient_registration', 'data' => $this->patientData()];

        $this->sync([$item]);
        $this->sync([$item])->assertJson(['synced_ids' => [3], 'duplicates' => 1]);

        $this->assertSame(1, Patient::count());
    }

    public function test_the_same_item_twice_in_one_batch_is_applied_once(): void
    {
        $item = ['id' => 1, 'uuid' => 'a2b3c4d5-e6f7-4a8b-9c0d-1e2f3a4b5c6d', 'type' => 'patient_registration', 'data' => $this->patientData()];

        $this->sync([$item, ['id' => 2] + $item])->assertJson(['synced_ids' => [1, 2], 'duplicates' => 1]);

        $this->assertSame(1, Patient::count());
    }

    public function test_different_uuids_are_separate_registrations(): void
    {
        $this->sync([
            ['id' => 1, 'uuid' => '11111111-1111-4111-8111-111111111111', 'type' => 'patient_registration', 'data' => $this->patientData()],
            ['id' => 2, 'uuid' => '22222222-2222-4222-8222-222222222222', 'type' => 'patient_registration', 'data' => $this->patientData(['first_name' => 'Bea'])],
        ])->assertJson(['synced_ids' => [1, 2]]);

        $this->assertSame(2, Patient::count());
    }

    public function test_a_failed_item_can_be_retried_after_it_is_fixed(): void
    {
        $uuid = '33333333-3333-4333-8333-333333333333';

        $this->sync([['id' => 5, 'uuid' => $uuid, 'type' => 'patient_registration', 'data' => $this->patientData(['phone' => null])]])
            ->assertJson(['success' => false, 'synced_ids' => []]);
        $this->assertDatabaseMissing('sync_receipts', ['key' => $uuid]);

        $this->sync([['id' => 5, 'uuid' => $uuid, 'type' => 'patient_registration', 'data' => $this->patientData()]])
            ->assertJson(['success' => true, 'synced_ids' => [5]]);
        $this->assertSame(1, Patient::count());
    }

    public function test_prenatal_visits_are_not_duplicated_either(): void
    {
        $patient = Patient::create($this->patientData() + ['barangay' => 'Bicao', 'status' => 'Active']);
        $record = MaternalRecord::create(['patient_id' => $patient->id, 'lmp' => '2026-03-01']);
        $item = ['id' => 9, 'uuid' => '44444444-4444-4444-8444-444444444444', 'type' => 'maternal_checkup', 'data' => [
            'maternal_record_id' => $record->id, 'weight_kg' => 60, 'bp' => '110/70',
        ]];

        $this->sync([$item]);
        $this->sync([$item]);

        $this->assertSame(1, MaternalCheckup::count());
    }

    public function test_key_for_prefers_a_valid_uuid(): void
    {
        $this->assertSame('6f1c2a0e-3b8d-4c5e-9f7a-1d2e3f4a5b6c', SyncReceipt::keyFor(['uuid' => '6F1C2A0E-3B8D-4C5E-9F7A-1D2E3F4A5B6C']));
        $this->assertSame(64, strlen(SyncReceipt::keyFor(['uuid' => 'not-a-uuid', 'type' => 'x', 'data' => []])));
    }

    // --- #12 Registration records only what was entered ------------------------------

    public function test_birth_doses_are_given_only_when_ticked_and_never_credited_to_a_made_up_person(): void
    {
        $this->actingAs($this->adminUser)->post('/register', $this->childData([
            'has_bcg_at_birth' => '1',
            'has_hepb_at_birth' => '0',
        ]))->assertRedirect(route('records'));

        $child = ChildRecord::sole();
        $this->assertTrue($child->has_bcg_at_birth);
        $this->assertFalse($child->has_hepb_at_birth);

        $bcg = Immunization::where('vaccine_name', 'BCG')->sole();
        $this->assertSame('Given', $bcg->status);
        $this->assertNull($bcg->administered_by);
        $this->assertSame('Given at birth', $bcg->remarks);

        $hepB = Immunization::where('vaccine_name', 'Hepatitis B')->sole();
        $this->assertSame('Scheduled', $hepB->status);
        $this->assertNull($hepB->given_date);

        $this->assertDatabaseMissing('immunizations', ['administered_by' => 'Midwife Elena']);
    }

    public function test_no_birth_measurement_is_invented_when_weight_or_height_is_missing(): void
    {
        $this->actingAs($this->adminUser)->post('/register', $this->childData(['birth_weight_kg' => 2.8]));

        $child = ChildRecord::sole();
        $this->assertEqualsWithDelta(2.8, $child->birth_weight_kg, 0.001);
        $this->assertNull($child->birth_height_cm);
        $this->assertSame(0, GrowthMeasurement::count());
    }

    public function test_birth_and_delivery_type_come_from_the_form(): void
    {
        $this->actingAs($this->adminUser)->post('/register', $this->childData(['birth_type' => 'Twin', 'delivery_type' => 'C-Section']));

        $this->assertDatabaseHas('child_records', ['birth_type' => 'Twin', 'delivery_type' => 'C-Section']);

        $this->actingAs($this->adminUser)
            ->post('/register', $this->childData(['first_name' => 'Other', 'delivery_type' => 'Teleport']))
            ->assertSessionHasErrors('delivery_type');
    }

    public function test_birth_plan_is_empty_unless_entered_and_can_be_edited(): void
    {
        $this->actingAs($this->adminUser)->post('/register', $this->patientData());
        $patient = Patient::sole();
        $this->assertNull($patient->maternalRecord->birth_plan);

        $this->actingAs($this->adminUser)->put("/patients/{$patient->id}", $this->patientData([
            'status' => 'Active',
            'birth_plan_facility' => 'Carmen District Hospital',
            'birth_plan_attendant' => 'Dr. Lim',
        ]))->assertRedirect(route('records'));

        $this->assertSame(
            ['facility' => 'Carmen District Hospital', 'attendant' => 'Dr. Lim'],
            $patient->maternalRecord->fresh()->birth_plan
        );

        $this->actingAs($this->adminUser)->get('/maternal?id=' . $patient->id)
            ->assertSee('Carmen District Hospital')
            ->assertDontSee('Midwife Elena');
    }

    public function test_birth_plan_entered_at_registration_is_saved(): void
    {
        $this->actingAs($this->adminUser)->post('/register', $this->patientData([
            'birth_plan_facility' => 'Bohol Provincial Hospital',
        ]));

        $this->assertSame(['facility' => 'Bohol Provincial Hospital'], MaternalRecord::sole()->birth_plan);
    }

    public function test_offline_registration_uses_the_same_rules_as_the_form(): void
    {
        $this->sync([['id' => 1, 'uuid' => '55555555-5555-4555-8555-555555555555', 'type' => 'patient_registration', 'data' => $this->patientData(['gender' => 'Unknown'])]])
            ->assertJson(['success' => false]);

        $this->sync([['id' => 2, 'uuid' => '66666666-6666-4666-8666-666666666666', 'type' => 'patient_registration', 'data' => $this->childData()]])
            ->assertJson(['success' => true]);

        $this->assertSame('Scheduled', Immunization::where('vaccine_name', 'BCG')->sole()->status);
        $this->assertSame(0, GrowthMeasurement::count());
    }

    // --- Phase 7: visit logs, growth metrics and vaccine doses entered offline -----------------

    protected function maternalRecord(): MaternalRecord
    {
        $patient = Patient::create($this->patientData() + ['barangay' => 'Bicao', 'status' => 'Active']);

        return MaternalRecord::create(['patient_id' => $patient->id, 'lmp' => '2026-03-01']);
    }

    protected function childRecord(): ChildRecord
    {
        $child = Patient::create($this->childData(['dob' => '2026-02-10']) + ['barangay' => 'Bicao', 'status' => 'Active']);

        return ChildRecord::create(['patient_id' => $child->id, 'birth_weight_kg' => 3.0, 'birth_height_cm' => 50.0]);
    }

    protected function scheduledDose(ChildRecord $childRecord, array $overrides = []): Immunization
    {
        return Immunization::create(array_merge([
            'child_record_id' => $childRecord->id,
            'vaccine_name' => 'Pentavalent (DPT-HepB-Hib)',
            'dose_number' => 1,
            'scheduled_date' => '2026-03-24',
            'status' => 'Scheduled',
        ], $overrides));
    }

    public function test_offline_visit_is_dated_when_it_was_entered_not_when_it_synced(): void
    {
        $record = $this->maternalRecord();

        $this->sync([['id' => 1, 'uuid' => '77777777-7777-4777-8777-777777777777', 'type' => 'maternal_checkup', 'data' => [
            'maternal_record_id' => $record->id, 'weight_kg' => 61.5, 'bp' => '110/70', 'notes' => 'Field visit',
            'recorded_at' => '2026-08-18T03:15:00.000Z',
        ]]])->assertJson(['success' => true, 'synced_ids' => [1]]);

        $checkup = MaternalCheckup::sole();
        $this->assertSame('2026-08-18', Carbon::parse($checkup->date)->toDateString());
        $this->assertSame('Midwife Rosa', $checkup->attendant);
        $this->assertSame('Field visit', $checkup->notes);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create_maternal_checkup',
            'model_id' => $checkup->id,
            'user_id' => $this->adminUser->id,
        ]);
    }

    public function test_entry_date_follows_the_app_timezone(): void
    {
        config(['app.timezone' => 'Asia/Manila']);
        $record = $this->maternalRecord();

        // 23:30 UTC on the 19th is 07:30 on the 20th in the Philippines.
        $this->sync([['id' => 1, 'uuid' => '88888888-8888-4888-8888-888888888888', 'type' => 'maternal_checkup', 'data' => [
            'maternal_record_id' => $record->id, 'weight_kg' => 61.5, 'bp' => '110/70',
            'recorded_at' => '2026-08-19T23:30:00.000Z',
        ]]])->assertJson(['success' => true]);

        $this->assertSame('2026-08-20', Carbon::parse(MaternalCheckup::sole()->date)->toDateString());
    }

    public function test_entries_dated_in_the_future_are_rejected(): void
    {
        $record = $this->maternalRecord();

        $this->sync([['id' => 1, 'uuid' => '99999999-9999-4999-8999-999999999999', 'type' => 'maternal_checkup', 'data' => [
            'maternal_record_id' => $record->id, 'weight_kg' => 61.5, 'bp' => '110/70',
            'recorded_at' => '2026-08-25T03:00:00.000Z',
        ]]])->assertJson(['success' => false, 'synced_ids' => []]);

        $this->assertSame(0, MaternalCheckup::count());
    }

    public function test_offline_growth_measurement_is_dated_and_aged_from_when_it_was_entered(): void
    {
        $childRecord = $this->childRecord();

        $this->sync([['id' => 4, 'uuid' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'type' => 'child_growth', 'data' => [
            'child_record_id' => $childRecord->id, 'weight_kg' => 6.8, 'height_cm' => 64.0,
            'recorded_at' => '2026-07-12T02:00:00.000Z',
        ]]])->assertJson(['success' => true, 'synced_ids' => [4]]);

        $measurement = GrowthMeasurement::sole();
        $this->assertSame('2026-07-12', Carbon::parse($measurement->date)->toDateString());
        $this->assertSame(5, $measurement->age_months);
        $this->assertDatabaseHas('audit_logs', ['action' => 'create_growth_measurement', 'model_id' => $measurement->id]);
    }

    public function test_offline_vaccine_dose_is_recorded_with_the_entry_date(): void
    {
        $dose = $this->scheduledDose($this->childRecord());

        $this->sync([['id' => 6, 'uuid' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'type' => 'immunization_update', 'data' => [
            'immunization_id' => $dose->id, 'recorded_at' => '2026-08-19T01:00:00.000Z',
        ]]])->assertJson(['success' => true, 'synced_ids' => [6]]);

        $dose->refresh();
        $this->assertSame('Given', $dose->status);
        $this->assertSame('2026-08-19', Carbon::parse($dose->given_date)->toDateString());
        $this->assertSame('Midwife Rosa', $dose->administered_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'administer_vaccine', 'model_id' => $dose->id]);
    }

    public function test_offline_vaccine_dose_does_not_overwrite_one_already_recorded(): void
    {
        $dose = $this->scheduledDose($this->childRecord(), [
            'status' => 'Given', 'given_date' => '2026-08-10', 'administered_by' => 'Midwife Elena',
        ]);

        // Still reported as synced, so the device stops retrying it.
        $this->sync([['id' => 6, 'uuid' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'type' => 'immunization_update', 'data' => [
            'immunization_id' => $dose->id, 'recorded_at' => '2026-08-19T01:00:00.000Z',
        ]]])->assertJson(['success' => true, 'synced_ids' => [6]]);

        $dose->refresh();
        $this->assertSame('2026-08-10', Carbon::parse($dose->given_date)->toDateString());
        $this->assertSame('Midwife Elena', $dose->administered_by);
    }

    public function test_offline_vaccine_item_must_name_an_existing_dose(): void
    {
        $this->sync([['id' => 6, 'uuid' => 'dddddddd-dddd-4ddd-8ddd-dddddddddddd', 'type' => 'immunization_update', 'data' => [
            'immunization_id' => 999,
        ]]])->assertJson(['success' => false, 'synced_ids' => []]);
    }

    public function test_clinical_forms_are_marked_for_offline_queueing(): void
    {
        $record = $this->maternalRecord();
        $childRecord = $this->childRecord();
        $dose = $this->scheduledDose($childRecord);

        $this->actingAs($this->adminUser)->get('/maternal?id=' . $record->patient_id)
            ->assertOk()
            ->assertSee('<meta name="offline-user-id" content="' . $this->adminUser->id . '">', false)
            ->assertSee('data-offline-type="maternal_checkup"', false)
            ->assertSee('data-offline-context=\'{"maternal_record_id":' . $record->id . '}\'', false);

        $this->actingAs($this->adminUser)->get('/growth?id=' . $childRecord->patient_id)
            ->assertOk()
            ->assertSee('data-offline-type="child_growth"', false)
            ->assertSee('data-offline-context=\'{"child_record_id":' . $childRecord->id . '}\'', false);

        $this->actingAs($this->adminUser)->get('/immunization?id=' . $childRecord->patient_id)
            ->assertOk()
            ->assertSee('data-offline-type="immunization_update"', false)
            ->assertSee('name="immunization_id" value="' . $dose->id . '"', false);
    }

    public function test_patients_get_no_offline_sync_identity(): void
    {
        $patientUser = User::create([
            'name' => 'Ana Reyes', 'email' => 'ana@example.com', 'password' => Hash::make('secret123'), 'role' => 'user',
        ]);

        $this->actingAs($patientUser)->get('/portal')
            ->assertOk()
            ->assertDontSee('offline-user-id', false);
    }
}
