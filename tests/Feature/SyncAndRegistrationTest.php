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
        $this->assertSame('Given at birth (recorded at registration)', $bcg->remarks);

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
}
