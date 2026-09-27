<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\MaternalRecord;
use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\GrowthMeasurement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SyncControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Admin Midwife',
            'email' => 'admin@maternal.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->regularUser = User::create([
            'name' => 'Regular Patient User',
            'email' => 'patient@maternal.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }

    /**
     * Test guests are redirected to login for the sync endpoints.
     */
    public function test_guests_cannot_access_sync_endpoints(): void
    {
        $this->get('/sync/token')->assertRedirect(route('login'));
        $this->postJson('/api/sync/patients', ['items' => []])->assertUnauthorized();
    }

    /**
     * Test the token endpoint returns a fresh CSRF token for an authenticated user.
     */
    public function test_token_endpoint_returns_csrf_token(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/sync/token');

        $response->assertOk();
        $response->assertJsonStructure(['token']);
        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * Test non-admin users cannot push offline registrations.
     */
    public function test_non_admin_cannot_sync_registrations(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/sync/patients', [
            'items' => [
                [
                    'id' => 1,
                    'type' => 'patient_registration',
                    'data' => [
                        'first_name' => 'Maria',
                        'last_name' => 'Santos',
                        'dob' => '1998-08-10',
                        'gender' => 'Female',
                        'phone' => '09123456789',
                        'email' => 'maria.santos@example.com',
                        'address' => 'Brgy. Bicao, Carmen, Bohol',
                        'emergency_contact_name' => 'Juan Santos',
                        'emergency_contact_phone' => '09987654321',
                        'registration_type' => 'Maternal',
                    ],
                ],
            ],
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('patients', 0);
    }

    /**
     * Test admin can sync an offline maternal patient registration.
     */
    public function test_admin_can_sync_maternal_registration(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson('/api/sync/patients', [
            'items' => [
                [
                    'id' => 7,
                    'type' => 'patient_registration',
                    'data' => [
                        'first_name' => 'Maria',
                        'last_name' => 'Santos',
                        'dob' => '1998-08-10',
                        'gender' => 'Female',
                        'phone' => '09123456789',
                        'email' => 'maria.santos@example.com',
                        'address' => 'Brgy. Bicao, Carmen, Bohol',
                        'emergency_contact_name' => 'Juan Santos',
                        'emergency_contact_phone' => '09987654321',
                        'registration_type' => 'Maternal',
                        'lmp' => '2026-01-01',
                        'gravida' => 2,
                        'para' => 1,
                        'philhealth_number' => '12-345678901-2',
                        'medical_history' => ['Hypertension'],
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'synced' => 1,
            'synced_ids' => [7],
            'errors' => [],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'maria.santos@example.com',
            'role' => 'user',
        ]);

        $patient = Patient::where('email', 'maria.santos@example.com')->first();
        $this->assertNotNull($patient);
        $this->assertNotNull($patient->user_id);

        $this->assertDatabaseHas('maternal_records', [
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01 00:00:00',
            'edd' => '2026-10-08 00:00:00',
            'gravida' => 2,
            'para' => 1,
        ]);
    }

    /**
     * Test admin can sync an offline child registration including the immunization schedule.
     */
    public function test_admin_can_sync_child_registration(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson('/api/sync/patients', [
            'items' => [
                [
                    'id' => 3,
                    'type' => 'patient_registration',
                    'data' => [
                        'first_name' => 'Juanito',
                        'last_name' => 'Santos',
                        'dob' => '2026-05-01',
                        'gender' => 'Male',
                        'phone' => '09123456789',
                        'address' => 'Brgy. Bicao, Carmen, Bohol',
                        'emergency_contact_name' => 'Maria Santos',
                        'emergency_contact_phone' => '09987654321',
                        'registration_type' => 'Child',
                        'birth_weight_kg' => 3.25,
                        'birth_height_cm' => 51.0,
                        'has_bcg_at_birth' => '1',
                        'has_hepb_at_birth' => '1',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'synced' => 1,
            'synced_ids' => [3],
        ]);

        $patient = Patient::where('first_name', 'Juanito')->first();
        $this->assertNotNull($patient);
        $this->assertNull($patient->user_id);

        $this->assertDatabaseHas('child_records', [
            'patient_id' => $patient->id,
            'birth_weight_kg' => 3.25,
            'birth_height_cm' => 51.0,
        ]);

        $childRecord = ChildRecord::where('patient_id', $patient->id)->first();

        $this->assertDatabaseHas('growth_measurements', [
            'child_record_id' => $childRecord->id,
            'age_months' => 0,
            'weight_kg' => 3.25,
        ]);

        $this->assertDatabaseHas('immunizations', [
            'child_record_id' => $childRecord->id,
            'vaccine_name' => 'BCG',
            'status' => 'Given',
        ]);

        $this->assertDatabaseHas('immunizations', [
            'child_record_id' => $childRecord->id,
            'vaccine_name' => 'MMR',
            'dose_number' => 2,
            'status' => 'Scheduled',
        ]);
    }

    /**
     * Test invalid items are reported as errors and do not create patients.
     */
    public function test_invalid_item_is_reported_as_error(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson('/api/sync/patients', [
            'items' => [
                [
                    'id' => 9,
                    'type' => 'patient_registration',
                    'data' => [
                        'first_name' => '',
                        'last_name' => 'Doe',
                        'registration_type' => 'Maternal',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'synced' => 0,
            'synced_ids' => [],
        ]);

        $this->assertNotEmpty($response->json('errors'));
        $this->assertDatabaseCount('patients', 0);
    }

    /**
     * Test multiple items are synced and only successful ids are returned.
     */
    public function test_multiple_items_are_synced(): void
    {
        $response = $this->actingAs($this->adminUser)->postJson('/api/sync/patients', [
            'items' => [
                [
                    'id' => 1,
                    'type' => 'patient_registration',
                    'data' => [
                        'first_name' => 'Jane',
                        'last_name' => 'Doe',
                        'dob' => '1995-05-15',
                        'gender' => 'Female',
                        'phone' => '09171234567',
                        'email' => 'jane@example.com',
                        'address' => 'Test Address',
                        'emergency_contact_name' => 'John Doe',
                        'emergency_contact_phone' => '09981234567',
                        'registration_type' => 'Maternal',
                    ],
                ],
                [
                    'id' => 2,
                    'type' => 'patient_registration',
                    'data' => [
                        'first_name' => 'John',
                        'last_name' => 'Smith',
                        'dob' => '1990-01-01',
                        'gender' => 'Male',
                        'phone' => '09171234567',
                        'email' => 'john@example.com',
                        'address' => 'Test Address',
                        'emergency_contact_name' => 'Jane Smith',
                        'emergency_contact_phone' => '09981234567',
                        'registration_type' => 'Child',
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'synced' => 2,
            'synced_ids' => [1, 2],
        ]);

        $this->assertDatabaseCount('patients', 2);
    }
}
