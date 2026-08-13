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
use Carbon\Carbon;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-06 00:00:00');

        // Create an admin user for testing
        $this->adminUser = User::create([
            'name' => 'Admin Midwife',
            'email' => 'admin@maternal.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // Create a regular user for testing
        $this->regularUser = User::create([
            'name' => 'Regular Patient User',
            'email' => 'patient@maternal.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
    }

    /**
     * Test guests are redirected to login.
     */
    public function test_guest_cannot_access_protected_routes(): void
    {
        $routes = [
            '/records',
            '/register',
            '/dashboard',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect(route('login'));
        }
    }

    /**
     * Test admin can access records page.
     */
    public function test_admin_can_access_records_page(): void
    {
        // Create some patients
        Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '1995-05-15',
            'gender' => 'Female',
            'phone' => '1234567890',
            'email' => 'jane@example.com',
            'address' => '123 Street',
            'registration_type' => 'Maternal',
        ]);

        Patient::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '2026-01-01',
            'gender' => 'Male',
            'phone' => '1234567890',
            'email' => null,
            'address' => '123 Street',
            'registration_type' => 'Child',
        ]);

        $response = $this->actingAs($this->adminUser)->get('/records');

        $response->assertStatus(200);
        $response->assertViewIs('records');
        $response->assertViewHas('patients');
        $response->assertViewHas('totalPatients', 2);
        $response->assertViewHas('maternalCases', 1);
        $response->assertViewHas('childRecords', 1);
    }

    /**
     * Test filter and search on records page.
     */
    public function test_admin_can_filter_and_search_records(): void
    {
        Patient::create([
            'first_name' => 'Alice',
            'last_name' => 'Smith',
            'dob' => '1995-05-15',
            'gender' => 'Female',
            'phone' => '1111111111',
            'email' => 'alice@example.com',
            'address' => '123 Street',
            'registration_type' => 'Maternal',
        ]);

        Patient::create([
            'first_name' => 'Bob',
            'last_name' => 'Jones',
            'dob' => '2026-01-01',
            'gender' => 'Male',
            'phone' => '2222222222',
            'email' => null,
            'address' => '123 Street',
            'registration_type' => 'Child',
        ]);

        // Search by name
        $response = $this->actingAs($this->adminUser)->get('/records?search=Alice');
        $response->assertStatus(200);
        $patients = $response->viewData('patients');
        $this->assertCount(1, $patients);
        $this->assertEquals('Alice', $patients->first()->first_name);

        // Filter by type
        $response = $this->actingAs($this->adminUser)->get('/records?type=Child');
        $response->assertStatus(200);
        $patients = $response->viewData('patients');
        $this->assertCount(1, $patients);
        $this->assertEquals('Bob', $patients->first()->first_name);
    }

    /**
     * Test GET /register view.
     */
    public function test_admin_can_view_registration_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/register');
        $response->assertStatus(200);
        $response->assertViewIs('register');
    }

    /**
     * Test POST /register for Maternal patient (creates user).
     */
    public function test_admin_can_register_maternal_patient_with_user(): void
    {
        $data = [
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
        ];

        $response = $this->actingAs($this->adminUser)->post('/register', $data);

        $response->assertRedirect(route('records'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'maria.santos@example.com',
            'role' => 'user',
        ]);

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'registration_type' => 'Maternal',
            'barangay' => 'Bicao',
        ]);

        $patient = Patient::where('email', 'maria.santos@example.com')->first();
        $this->assertNotNull($patient->user_id);

        $this->assertDatabaseHas('maternal_records', [
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01 00:00:00',
            'edd' => '2026-10-08 00:00:00',
            'gravida' => 2,
            'para' => 1,
            'philhealth_number' => '12-345678901-2',
        ]);
    }

    /**
     * Test POST /register for Child patient.
     */
    public function test_admin_can_register_child_patient(): void
    {
        $data = [
            'first_name' => 'Juanito',
            'last_name' => 'Santos',
            'dob' => '2026-05-01',
            'gender' => 'Male',
            'phone' => '09123456789',
            'email' => null, // No email/user account for child usually
            'address' => 'Brgy. Bicao, Carmen, Bohol',
            'emergency_contact_name' => 'Maria Santos',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Child',
            'birth_weight_kg' => 3.25,
            'birth_height_cm' => 51.0,
        ];

        $response = $this->actingAs($this->adminUser)->post('/register', $data);

        $response->assertRedirect(route('records'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('patients', [
            'first_name' => 'Juanito',
            'last_name' => 'Santos',
            'registration_type' => 'Child',
        ]);

        $patient = Patient::where('first_name', 'Juanito')->first();
        $this->assertNull($patient->user_id);

        $this->assertDatabaseHas('child_records', [
            'patient_id' => $patient->id,
            'birth_weight_kg' => 3.25,
            'birth_height_cm' => 51.0,
        ]);

        $childRecord = ChildRecord::where('patient_id', $patient->id)->first();

        // Check that initial growth measurement at birth is created
        $this->assertDatabaseHas('growth_measurements', [
            'child_record_id' => $childRecord->id,
            'age_months' => 0,
            'weight_kg' => 3.25,
            'height_cm' => 51.0,
            'status' => 'Normal',
        ]);

        // Check that immunization schedule is generated
        $this->assertDatabaseHas('immunizations', [
            'child_record_id' => $childRecord->id,
            'vaccine_name' => 'BCG',
            'status' => 'Given',
        ]);

        $this->assertDatabaseHas('immunizations', [
            'child_record_id' => $childRecord->id,
            'vaccine_name' => 'Pentavalent (DPT-HepB-Hib)',
            'dose_number' => 1,
            'status' => 'Scheduled',
        ]);
    }
}