<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChildRecord;
use App\Models\Patient;
use App\Models\User;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccessHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $patientUser;
    protected Patient $patientRecord;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-20 10:00:00');

        $this->adminUser = User::create([
            'name' => 'Admin Midwife',
            'email' => 'admin@maternal.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);

        $this->patientUser = User::create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);

        $this->patientRecord = $this->makePatient([
            'user_id' => $this->patientUser->id,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@example.com',
            'status' => 'High Risk',
        ]);
    }

    protected function makePatient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'first_name' => 'Elena',
            'last_name' => 'Cruz',
            'dob' => '1995-01-01',
            'gender' => 'Female',
            'phone' => '09171234567',
            'address' => 'Purok 1',
            'barangay' => 'Bicao',
            'emergency_contact_name' => 'Juan Cruz',
            'emergency_contact_phone' => '09222222222',
            'registration_type' => 'Maternal',
            'status' => 'Active',
        ], $overrides));
    }

    protected function registrationData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'dob' => '1998-04-12',
            'gender' => 'Female',
            'phone' => '09123456789',
            'email' => 'ana.reyes@example.com',
            'address' => 'Purok 2, Bicao',
            'emergency_contact_name' => 'Pedro Reyes',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Maternal',
        ], $overrides);
    }

    // --- #1 SMS center is staff-only -------------------------------------------------

    public function test_patient_cannot_open_sms_center_or_see_gateway_password(): void
    {
        Http::fake();
        $settingsPath = storage_path('app/sms_settings.json');
        $original = file_exists($settingsPath) ? file_get_contents($settingsPath) : null;

        try {
            app(SmsService::class)->saveSettings('http://127.0.0.1:9', 'gwuser', 'GW-SECRET-PW');

            $response = $this->actingAs($this->patientUser)->get('/sms');

            $response->assertStatus(403);
            $this->assertStringNotContainsString('GW-SECRET-PW', $response->getContent());
        } finally {
            $original === null ? @unlink($settingsPath) : file_put_contents($settingsPath, $original);
        }
    }

    public function test_patient_cannot_use_sms_actions(): void
    {
        $this->actingAs($this->patientUser)->get('/sms/status')->assertStatus(403);
        $this->actingAs($this->patientUser)->post('/sms/settings', ['url' => 'http://evil.test'])->assertStatus(403);
        $this->actingAs($this->patientUser)->post('/sms', ['patient_id' => $this->patientRecord->id, 'message' => 'hi'])->assertStatus(403);
    }

    // --- #2 Dashboard is staff-only --------------------------------------------------

    public function test_patient_is_redirected_from_staff_dashboard_to_portal(): void
    {
        $this->actingAs($this->patientUser)->get('/dashboard')->assertRedirect('/portal');
    }

    // --- #3 Patients cannot edit their own clinical record ---------------------------

    public function test_patient_cannot_edit_or_update_own_record(): void
    {
        $this->actingAs($this->patientUser)
            ->get('/patients/' . $this->patientRecord->id . '/edit')
            ->assertStatus(403);

        $this->actingAs($this->patientUser)->put('/patients/' . $this->patientRecord->id, [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'dob' => '1995-01-01',
            'gender' => 'Female',
            'phone' => '09171234567',
            'address' => 'Purok 1',
            'emergency_contact_name' => 'Juan Cruz',
            'emergency_contact_phone' => '09222222222',
            'status' => 'Completed',
        ])->assertStatus(403);

        $this->assertSame('High Risk', $this->patientRecord->fresh()->status);
    }

    // --- #4 Chat attachments are private ---------------------------------------------

    public function test_chat_attachment_is_private_and_only_served_to_participants(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->actingAs($this->adminUser)->post('/messaging', [
            'receiver_id' => $this->patientUser->id,
            'message' => 'Your lab result',
            'file' => UploadedFile::fake()->create('lab.pdf', 50, 'application/pdf'),
        ]);

        $message = ChatMessage::latest()->first();
        Storage::disk('local')->assertExists($message->attachment_path);
        Storage::disk('public')->assertMissing($message->attachment_path);
        $this->assertSame(route('messaging.attachment', $message), $message->attachment_url);

        $this->actingAs($this->patientUser)->get($message->attachment_url)->assertOk();
        $this->actingAs($this->adminUser)->get($message->attachment_url)->assertOk();

        $stranger = User::create([
            'name' => 'Other Patient',
            'email' => 'other@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);
        $this->actingAs($stranger)->get($message->attachment_url)->assertStatus(403);
    }

    public function test_guest_cannot_download_attachment(): void
    {
        Storage::fake('local');

        $message = ChatMessage::create([
            'sender_id' => $this->adminUser->id,
            'receiver_id' => $this->patientUser->id,
            'message' => 'file',
            'attachment_path' => 'attachments/x.pdf',
            'attachment_name' => 'x.pdf',
            'attachment_type' => 'application/pdf',
        ]);
        Storage::disk('local')->put('attachments/x.pdf', 'pdf');

        $this->get(route('messaging.attachment', $message))->assertRedirect(route('login'));
    }

    // --- #5 Children are linked to their mother --------------------------------------

    public function test_child_registration_links_mother_and_mother_sees_child_in_portal(): void
    {
        $this->actingAs($this->adminUser)->post('/register', $this->registrationData([
            'first_name' => 'Juanito',
            'last_name' => 'Santos',
            'dob' => '2026-05-01',
            'gender' => 'Male',
            'email' => null,
            'registration_type' => 'Child',
            'mother_id' => $this->patientRecord->id,
        ]))->assertRedirect(route('records'));

        $child = Patient::where('first_name', 'Juanito')->first();
        $this->assertSame($this->patientRecord->id, $child->childRecord->mother_id);

        $this->actingAs($this->patientUser)->get('/portal')->assertViewHas('childrenCount', 1);
        $this->actingAs($this->patientUser)->get('/growth')->assertOk()->assertViewIs('patient.growth');
        $this->actingAs($this->patientUser)->get('/immunization')->assertOk()->assertViewIs('patient.immunization');
    }

    public function test_mother_id_must_be_a_maternal_patient(): void
    {
        $otherChild = $this->makePatient(['first_name' => 'Kid', 'registration_type' => 'Child']);

        $this->actingAs($this->adminUser)->post('/register', $this->registrationData([
            'email' => null,
            'registration_type' => 'Child',
            'mother_id' => $otherChild->id,
        ]))->assertSessionHasErrors('mother_id');
    }

    public function test_admin_can_link_mother_when_editing_child(): void
    {
        $child = $this->makePatient(['first_name' => 'Nena', 'registration_type' => 'Child']);
        ChildRecord::create(['patient_id' => $child->id]);

        $this->actingAs($this->adminUser)->put('/patients/' . $child->id, [
            'first_name' => 'Nena',
            'last_name' => 'Cruz',
            'dob' => '2026-01-01',
            'gender' => 'Female',
            'phone' => '09171234567',
            'address' => 'Purok 1',
            'emergency_contact_name' => 'Juan Cruz',
            'emergency_contact_phone' => '09222222222',
            'status' => 'Active',
            'birth_type' => 'Single',
            'delivery_type' => 'Normal',
            'mother_id' => $this->patientRecord->id,
        ])->assertRedirect(route('records'));

        $this->assertSame($this->patientRecord->id, $child->childRecord->fresh()->mother_id);
    }

    public function test_offline_sync_links_child_to_mother(): void
    {
        $this->actingAs($this->adminUser)->postJson('/api/sync/batch', [
            'items' => [[
                'id' => 1,
                'type' => 'patient_registration',
                'data' => $this->registrationData([
                    'first_name' => 'Offline',
                    'email' => null,
                    'registration_type' => 'Child',
                    'mother_id' => $this->patientRecord->id,
                ]),
            ]],
        ])->assertJson(['success' => true]);

        $child = Patient::where('first_name', 'Offline')->first();
        $this->assertSame($this->patientRecord->id, $child->childRecord->mother_id);
    }

    // --- #6 Newly registered patients can actually log in ---------------------------

    public function test_registration_shows_temporary_password_that_works_for_login(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/register', $this->registrationData());

        $response->assertSessionHas('portal_credentials');
        $credentials = session('portal_credentials');
        $this->assertSame('ana.reyes@example.com', $credentials['email']);

        $this->followRedirects($response)->assertSee($credentials['password']);

        auth()->logout();
        $this->post('/', [
            'email' => 'ana.reyes@example.com',
            'password' => $credentials['password'],
            'role' => 'user',
        ])->assertRedirect('/portal');
        $this->assertAuthenticated();
    }

    public function test_patient_email_cannot_be_a_staff_account(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->registrationData(['email' => 'admin@maternal.com']))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('patients', ['email' => 'admin@maternal.com']);
    }

    public function test_admin_can_reset_patient_portal_password(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->from('/patients/' . $this->patientRecord->id . '/edit')
            ->post('/patients/' . $this->patientRecord->id . '/portal-password');

        $response->assertSessionHas('portal_credentials');
        $newPassword = session('portal_credentials')['password'];

        $this->assertTrue(Hash::check($newPassword, $this->patientUser->fresh()->password));
        $this->assertDatabaseHas('audit_logs', ['action' => 'reset_patient_portal_password']);
    }

    public function test_admin_can_create_portal_account_for_patient_without_one(): void
    {
        $patient = $this->makePatient(['email' => 'late.email@example.com']);

        $this->actingAs($this->adminUser)
            ->post('/patients/' . $patient->id . '/portal-password')
            ->assertSessionHas('portal_credentials');

        $user = User::where('email', 'late.email@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('user', $user->role);
        $this->assertSame($user->id, $patient->fresh()->user_id);
    }

    public function test_patient_cannot_reset_portal_passwords(): void
    {
        $this->actingAs($this->patientUser)
            ->post('/patients/' . $this->patientRecord->id . '/portal-password')
            ->assertStatus(403);
    }
}
