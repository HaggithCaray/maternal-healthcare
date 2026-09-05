<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DomainControllersTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $patientUser;

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
    }

    public function test_admin_can_register_patient_securely_without_default_password(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/register', [
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
            'lmp' => '2026-01-10',
            'gravida' => 2,
            'para' => 1,
        ]);

        $response->assertRedirect(route('records'));
        $this->assertDatabaseHas('patients', [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'ana.reyes@example.com',
        ]);

        $createdUser = User::where('email', 'ana.reyes@example.com')->first();
        $this->assertNotNull($createdUser);
        // Ensure default password 'password' is NOT used
        $this->assertFalse(Hash::check('password', $createdUser->password));

        // Ensure audit log was generated
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create_patient',
        ]);
    }

    public function test_patient_policy_blocks_unauthorized_record_access(): void
    {
        $otherPatient = Patient::create([
            'user_id' => null,
            'first_name' => 'Elena',
            'last_name' => 'Cruz',
            'dob' => '1992-06-15',
            'gender' => 'Female',
            'phone' => '09111111111',
            'address' => 'Purok 1',
            'emergency_contact_name' => 'Juan Cruz',
            'emergency_contact_phone' => '09222222222',
            'registration_type' => 'Maternal',
        ]);

        // Regular user should not be able to edit another patient's record
        $response = $this->actingAs($this->patientUser)->get('/patients/' . $otherPatient->id . '/edit');
        $response->assertStatus(403);
    }

    public function test_chat_file_upload_security_validation(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('medical_chart.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post('/messaging', [
            'receiver_id' => $this->patientUser->id,
            'message' => 'Here is your lab test result.',
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->adminUser->id,
            'receiver_id' => $this->patientUser->id,
            'message' => 'Here is your lab test result.',
            'attachment_name' => 'medical_chart.pdf',
        ]);
    }

    public function test_sync_controller_batch_endpoint(): void
    {
        $payload = [
            'items' => [
                [
                    'id' => 101,
                    'type' => 'patient_registration',
                    'data' => [
                        'first_name' => 'Lourdes',
                        'last_name' => 'Dela Cruz',
                        'dob' => '1996-03-22',
                        'gender' => 'Female',
                        'phone' => '09333333333',
                        'email' => 'lourdes@example.com',
                        'address' => 'Purok 4',
                        'emergency_contact_name' => 'Carlos Dela Cruz',
                        'emergency_contact_phone' => '09444444444',
                        'registration_type' => 'Maternal',
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->postJson('/api/sync/batch', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'synced' => 1,
            'synced_ids' => [101],
        ]);

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Lourdes',
            'last_name' => 'Dela Cruz',
        ]);
    }

    public function test_patient_user_cannot_access_records_directory(): void
    {
        $response = $this->actingAs($this->patientUser)->get('/records');
        $response->assertStatus(403);
    }

    public function test_patient_user_cannot_view_registration_form(): void
    {
        $response = $this->actingAs($this->patientUser)->get('/register');
        $response->assertStatus(403);
    }

    public function test_patient_user_cannot_create_patient_record(): void
    {
        $response = $this->actingAs($this->patientUser)->post('/register', [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'dob' => '1998-04-12',
            'gender' => 'Female',
            'phone' => '09123456789',
            'email' => 'ana.reyes@example.com',
            'address' => 'Purok 2, Bicao',
            'registration_type' => 'Maternal',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('patients', [
            'email' => 'ana.reyes@example.com',
        ]);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'create_patient',
        ]);
    }

    public function test_patient_user_cannot_access_reports_dashboard(): void
    {
        $response = $this->actingAs($this->patientUser)->get('/reports');
        $response->assertStatus(403);
    }

    public function test_chat_upload_uses_safe_extension_not_client_filename(): void
    {
        Storage::fake('public');

        // Filename whose final extension (xyz) is NOT in the allowed set, but whose
        // content MIME (application/pdf) passes the mimes rule. The stored file must
        // use the content-derived extension (.pdf), never the client-supplied one (.xyz).
        $file = UploadedFile::fake()->create('chart.xyz', 100, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post('/messaging', [
            'receiver_id' => $this->patientUser->id,
            'message' => 'Client filename extension mismatch',
            'file' => $file,
        ]);

        $response->assertRedirect();

        $message = ChatMessage::latest()->first();
        $this->assertNotNull($message);
        $this->assertNotNull($message->attachment_path);
        $this->assertStringEndsWith('.pdf', $message->attachment_path);
        $this->assertStringNotContainsString('.xyz', $message->attachment_path);
    }

    public function test_chat_upload_rejects_php_client_filename(): void
    {
        Storage::fake('public');

        // Laravel's mimes rule blocks .php client extensions outright (shouldBlockPhpUpload),
        // so a PHP-named file must never reach storage even with valid image content.
        $file = UploadedFile::fake()->create('shell.php', 100, 'image/jpeg');

        $response = $this->actingAs($this->adminUser)->post('/messaging', [
            'receiver_id' => $this->patientUser->id,
            'message' => 'PHP filename attempt',
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseCount('chat_messages', 0);
    }
}
