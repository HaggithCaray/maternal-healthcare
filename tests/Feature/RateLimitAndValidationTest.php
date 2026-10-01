<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\SmsMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RateLimitAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $patientUser;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-20 10:00:00');

        $this->adminUser = User::create([
            'name' => 'Midwife Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin',
        ]);
        $this->patientUser = User::create([
            'name' => 'Ana Reyes', 'email' => 'ana@example.com', 'password' => Hash::make('secret123'), 'role' => 'user',
        ]);
    }

    protected function patient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'first_name' => 'Nico',
            'last_name' => 'Reyes',
            'dob' => '2026-06-01',
            'gender' => 'Male',
            'phone' => '09123456789',
            'address' => 'Purok 2',
            'emergency_contact_name' => 'Ana Reyes',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Child',
            'barangay' => 'Bicao',
            'status' => 'Active',
        ], $overrides));
    }

    protected function childWithRecord(): Patient
    {
        $child = $this->patient();
        ChildRecord::create(['patient_id' => $child->id, 'birth_weight_kg' => 3.0, 'birth_height_cm' => 50.0]);

        return $child;
    }

    protected function editData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Nico',
            'last_name' => 'Reyes',
            'dob' => '2026-06-01',
            'gender' => 'Male',
            'phone' => '09123456789',
            'address' => 'Purok 2',
            'emergency_contact_name' => 'Ana Reyes',
            'emergency_contact_phone' => '09987654321',
            'status' => 'Active',
        ], $overrides);
    }

    // --- Rate limits ------------------------------------------------------------------------

    public function test_one_address_cannot_try_a_password_against_many_accounts(): void
    {
        foreach (range(1, 20) as $i) {
            $this->post('/', ['email' => "user{$i}@example.com", 'password' => 'Password1', 'role' => 'admin'])
                ->assertSessionHasErrors('email');
        }

        // The 21st attempt is blocked whichever account it names, even with the right password.
        $this->from('/')->post('/', ['email' => 'rosa@health.test', 'password' => 'secret123', 'role' => 'admin'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('throttle');
        $this->assertGuest();
    }

    public function test_password_change_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->actingAs($this->patientUser)->from('/account/password')->put('/account/password', [
                'current_password' => "guess{$i}", 'password' => 'Newpass123', 'password_confirmation' => 'Newpass123',
            ])->assertSessionHasErrors('current_password');
        }

        $this->actingAs($this->patientUser)->from('/account/password')->put('/account/password', [
            'current_password' => 'secret123', 'password' => 'Newpass123', 'password_confirmation' => 'Newpass123',
        ])->assertRedirect('/account/password')->assertSessionHas('error');

        $this->assertTrue(Hash::check('secret123', $this->patientUser->fresh()->password));
    }

    public function test_chat_sending_is_rate_limited_but_opening_the_chat_is_not(): void
    {
        foreach (range(1, 20) as $i) {
            $this->actingAs($this->patientUser)->postJson('/messaging', ['message' => "Hello {$i}"])->assertOk();
        }

        $this->actingAs($this->patientUser)->postJson('/messaging', ['message' => 'One more'])
            ->assertStatus(429)
            ->assertJsonPath('message', fn (string $message) => str_starts_with($message, 'Too many attempts. Please wait'));
        $this->assertSame(20, ChatMessage::count());

        $this->actingAs($this->patientUser)->get('/messaging')->assertOk();

        // The limit resets after a minute.
        $this->travel(61)->seconds();
        $this->actingAs($this->patientUser)->postJson('/messaging', ['message' => 'Later'])->assertOk();
    }

    public function test_chat_attachments_are_capped_per_hour(): void
    {
        Storage::fake('local');

        foreach (range(1, 30) as $i) {
            if ($i === 21) {
                $this->travel(61)->seconds(); // stay under the per-minute message limit
            }
            $this->actingAs($this->patientUser)->postJson('/messaging', [
                'file' => UploadedFile::fake()->create("lab{$i}.pdf", 10, 'application/pdf'),
            ])->assertOk();
        }

        $this->travel(61)->seconds();
        $this->actingAs($this->patientUser)->postJson('/messaging', [
            'file' => UploadedFile::fake()->create('lab31.pdf', 10, 'application/pdf'),
        ])->assertStatus(429);

        // Text messages are still allowed.
        $this->actingAs($this->patientUser)->postJson('/messaging', ['message' => 'Text only'])->assertOk();
    }

    public function test_sms_sending_is_rate_limited(): void
    {
        Http::fake(['*' => Http::response(['status' => 'pass'], 200)]);
        $patient = $this->patient();

        foreach (range(1, 10) as $i) {
            $this->actingAs($this->adminUser)->from('/sms')->post('/sms', ['patient_id' => $patient->id, 'message' => "Reminder {$i}"]);
        }

        $this->actingAs($this->adminUser)->from('/sms')->post('/sms', ['patient_id' => $patient->id, 'message' => 'Reminder 11'])
            ->assertRedirect('/sms')
            ->assertSessionHas('error');
        $this->assertSame(10, SmsMessage::count());

        $this->actingAs($this->adminUser)->get('/sms')->assertOk();
    }

    // --- Edit Patient -----------------------------------------------------------------------

    public function test_edit_patient_rejects_what_registration_would_refuse(): void
    {
        $child = $this->childWithRecord();

        $this->actingAs($this->adminUser)->put("/patients/{$child->id}", $this->editData([
            'dob' => '2030-01-01',
            'gender' => 'Unknown',
            'phone' => str_repeat('9', 300),
            'birth_weight_kg' => '500',
            'birth_height_cm' => 'abc',
            'head_circumference_cm' => '100',
            'occupation' => str_repeat('x', 300),
        ]))->assertSessionHasErrors([
            'dob', 'gender', 'phone', 'birth_weight_kg', 'birth_height_cm', 'head_circumference_cm', 'occupation',
        ]);

        $child->refresh();
        $this->assertSame('Male', $child->gender);
        $this->assertSame('09123456789', $child->phone);
        $this->assertEquals(3.0, $child->childRecord->birth_weight_kg);
    }

    public function test_edit_patient_rejects_a_mother_link_to_a_non_maternal_patient(): void
    {
        $child = $this->childWithRecord();
        $otherChild = $this->patient(['first_name' => 'Bea']);

        $this->actingAs($this->adminUser)->put("/patients/{$child->id}", $this->editData(['mother_id' => $otherChild->id]))
            ->assertSessionHasErrors('mother_id');
    }

    public function test_edit_patient_still_saves_valid_changes(): void
    {
        $child = $this->childWithRecord();

        $this->actingAs($this->adminUser)->put("/patients/{$child->id}", $this->editData([
            'phone' => '09170000000', 'birth_weight_kg' => '3.25', 'head_circumference_cm' => '34.5', 'has_vitamin_k' => '1',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('records'));

        $child->refresh();
        $this->assertSame('09170000000', $child->phone);
        $this->assertEquals(3.25, $child->childRecord->birth_weight_kg);
        $this->assertEquals(34.5, $child->childRecord->head_circumference_cm);
        $this->assertTrue((bool) $child->childRecord->has_vitamin_k);
    }

    // --- Mark Given -------------------------------------------------------------------------

    public function test_mark_given_does_not_overwrite_a_dose_already_recorded(): void
    {
        $child = $this->childWithRecord();
        $dose = Immunization::create([
            'child_record_id' => $child->childRecord->id, 'vaccine_name' => 'OPV', 'dose_number' => 1,
            'scheduled_date' => '2026-07-13', 'status' => 'Given', 'given_date' => '2026-07-13', 'administered_by' => 'Midwife Elena',
        ]);

        $this->actingAs($this->adminUser)->from("/immunization?id={$child->id}")
            ->post("/immunization?id={$child->id}", ['immunization_id' => $dose->id])
            ->assertSessionHas('warning');

        $dose->refresh();
        $this->assertSame('2026-07-13', $dose->given_date->toDateString());
        $this->assertSame('Midwife Elena', $dose->administered_by);
    }

    public function test_mark_given_validates_its_input(): void
    {
        $child = $this->childWithRecord();
        $dose = Immunization::create([
            'child_record_id' => $child->childRecord->id, 'vaccine_name' => 'OPV', 'dose_number' => 1,
            'scheduled_date' => '2026-07-13', 'status' => 'Scheduled',
        ]);

        $this->actingAs($this->adminUser)->post("/immunization?id={$child->id}", [])
            ->assertSessionHasErrors('immunization_id');
        $this->actingAs($this->adminUser)->post("/immunization?id={$child->id}", ['immunization_id' => $dose->id, 'remarks' => str_repeat('r', 300)])
            ->assertSessionHasErrors('remarks');

        $this->assertSame('Scheduled', $dose->fresh()->status);
    }

    public function test_vaccine_page_shows_the_result_of_marking_a_dose(): void
    {
        $child = $this->childWithRecord();
        $dose = Immunization::create([
            'child_record_id' => $child->childRecord->id, 'vaccine_name' => 'OPV', 'dose_number' => 1,
            'scheduled_date' => '2026-07-13', 'status' => 'Scheduled',
        ]);

        $this->actingAs($this->adminUser)->from("/immunization?id={$child->id}")
            ->followingRedirects()
            ->post("/immunization?id={$child->id}", ['immunization_id' => $dose->id])
            ->assertSee('Vaccine marked as administered!');
    }
}
