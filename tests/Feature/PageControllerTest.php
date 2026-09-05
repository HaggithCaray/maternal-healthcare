<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\MaternalRecord;
use App\Models\MaternalCheckup;
use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\GrowthMeasurement;
use App\Models\SmsMessage;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
            '/maternal',
            '/growth',
            '/immunization',
            '/sms',
            '/messaging',
            '/reports',
            '/portal',
            '/admin'
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect(route('login'));
        }
    }

    /**
     * Test guests cannot access edit patient route.
     */
    public function test_guest_cannot_access_edit_patient(): void
    {
        $patient = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '1995-05-15',
            'gender' => 'Female',
            'phone' => '1234567890',
            'address' => '123 Street',
            'registration_type' => 'Maternal',
        ]);

        $response = $this->get('/patients/' . $patient->id . '/edit');
        $response->assertRedirect(route('login'));
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

    /**
     * Test admin can view edit form for maternal patient.
     */
    public function test_admin_can_view_edit_form_for_maternal_patient(): void
    {
        $patient = Patient::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'dob' => '1998-08-10',
            'gender' => 'Female',
            'phone' => '09123456789',
            'email' => 'maria@example.com',
            'address' => 'Brgy. Bicao, Carmen, Bohol',
            'emergency_contact_name' => 'Juan Santos',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Maternal',
        ]);

        $maternalRecord = MaternalRecord::create([
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01',
            'edd' => '2026-10-08',
            'gravida' => 2,
            'para' => 1,
            'philhealth_number' => '12-345678901-2',
            'medical_history' => ['Hypertension'],
        ]);

        $response = $this->actingAs($this->adminUser)->get('/patients/' . $patient->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewIs('patient.edit');
        $response->assertViewHas('patient');
    }

    /**
     * Test admin can view edit form for child patient.
     */
    public function test_admin_can_view_edit_form_for_child_patient(): void
    {
        $patient = Patient::create([
            'first_name' => 'Baby',
            'last_name' => 'Doe',
            'dob' => '2026-01-01',
            'gender' => 'Male',
            'phone' => '1234567890',
            'address' => '123 Street',
            'registration_type' => 'Child',
        ]);

        $childRecord = ChildRecord::create([
            'patient_id' => $patient->id,
            'birth_weight_kg' => 3.0,
            'birth_height_cm' => 50.0,
        ]);

        $response = $this->actingAs($this->adminUser)->get('/patients/' . $patient->id . '/edit');

        $response->assertStatus(200);
        $response->assertViewIs('patient.edit');
        $response->assertViewHas('patient');
    }

    /**
     * Test admin can update maternal patient.
     */
    public function test_admin_can_update_maternal_patient(): void
    {
        $patient = Patient::create([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'dob' => '1998-08-10',
            'gender' => 'Female',
            'phone' => '09123456789',
            'email' => 'maria@example.com',
            'address' => 'Brgy. Bicao, Carmen, Bohol',
            'emergency_contact_name' => 'Juan Santos',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Maternal',
            'status' => 'Active',
        ]);

        $maternalRecord = MaternalRecord::create([
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01',
            'edd' => '2026-10-08',
            'gravida' => 1,
            'para' => 0,
        ]);

        $updateData = [
            'first_name' => 'Maria Clara',
            'last_name' => 'Santos-Dizon',
            'dob' => '1998-08-10',
            'gender' => 'Female',
            'phone' => '09123456789',
            'email' => 'maria.clara@example.com',
            'address' => 'Updated Address, Brgy. Bicao',
            'emergency_contact_name' => 'Juan Dizon',
            'emergency_contact_phone' => '09987654321',
            'status' => 'High Risk',
            'occupation' => 'Teacher',
            'lmp' => '2026-02-01',
            'gravida' => 3,
            'para' => 2,
            'philhealth_number' => '99-887766554-3',
            'medical_history' => ['Diabetes' => '1', 'Anemia' => '1'],
            'allergies' => 'Penicillin',
        ];

        $response = $this->actingAs($this->adminUser)->put('/patients/' . $patient->id, $updateData);

        $response->assertRedirect(route('records'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'first_name' => 'Maria Clara',
            'last_name' => 'Santos-Dizon',
            'email' => 'maria.clara@example.com',
            'status' => 'High Risk',
            'occupation' => 'Teacher',
        ]);

        $this->assertDatabaseHas('maternal_records', [
            'patient_id' => $patient->id,
            'lmp' => '2026-02-01 00:00:00',
            'edd' => '2026-11-08 00:00:00',
            'gravida' => 3,
            'para' => 2,
            'philhealth_number' => '99-887766554-3',
            'allergies' => 'Penicillin',
        ]);

        $maternalRecord->refresh();
        $this->assertContains('Diabetes', $maternalRecord->medical_history);
        $this->assertContains('Anemia', $maternalRecord->medical_history);
        $this->assertNotContains('Hypertension', $maternalRecord->medical_history);
    }

    /**
     * Test admin can update child patient.
     */
    public function test_admin_can_update_child_patient(): void
    {
        $patient = Patient::create([
            'first_name' => 'Baby',
            'last_name' => 'Doe',
            'dob' => '2026-01-01',
            'gender' => 'Male',
            'phone' => '09123456789',
            'address' => '123 Street',
            'registration_type' => 'Child',
            'status' => 'Active',
        ]);

        $childRecord = ChildRecord::create([
            'patient_id' => $patient->id,
            'birth_weight_kg' => 3.0,
            'birth_height_cm' => 50.0,
            'has_newborn_screening' => false,
            'has_bcg_at_birth' => true,
        ]);

        $updateData = [
            'first_name' => 'Baby Jane',
            'last_name' => 'Doe',
            'dob' => '2026-01-01',
            'gender' => 'Male',
            'phone' => '09123456789',
            'address' => 'Updated Address',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '09987654321',
            'status' => 'Completed',
            'birth_weight_kg' => 3.25,
            'birth_height_cm' => 51.0,
            'head_circumference_cm' => 34.5,
            'birth_type' => 'Single',
            'delivery_type' => 'Normal',
            'has_newborn_screening' => '1',
            'has_hearing_screening' => '1',
            'has_bcg_at_birth' => '1',
        ];

        $response = $this->actingAs($this->adminUser)->put('/patients/' . $patient->id, $updateData);

        $response->assertRedirect(route('records'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('patients', [
            'id' => $patient->id,
            'first_name' => 'Baby Jane',
            'status' => 'Completed',
        ]);

        $this->assertDatabaseHas('child_records', [
            'patient_id' => $patient->id,
            'birth_weight_kg' => 3.25,
            'birth_height_cm' => 51.0,
            'head_circumference_cm' => 34.5,
            'has_newborn_screening' => true,
            'has_hearing_screening' => true,
            'has_bcg_at_birth' => true,
        ]);
    }

    /**
     * Test GET /maternal for admin and user.
     */
    public function test_maternal_page_access_and_records(): void
    {
        // 1. Create a patient and maternal record
        $patient = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '1995-05-15',
            'gender' => 'Female',
            'phone' => '1234567890',
            'email' => 'jane@example.com',
            'address' => 'Test Address',
            'registration_type' => 'Maternal',
            'user_id' => $this->regularUser->id,
        ]);

        $maternalRecord = MaternalRecord::create([
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01',
            'edd' => '2026-10-08',
            'gravida' => 1,
            'para' => 0,
        ]);

        // Admin access to specific patient maternal page
        $response = $this->actingAs($this->adminUser)->get('/maternal?id=' . $patient->id);
        $response->assertStatus(200);
        $response->assertViewIs('maternal');
        $response->assertViewHas('patient');

        // User access to their own maternal page
        $response2 = $this->actingAs($this->regularUser)->get('/maternal');
        $response2->assertStatus(200);
        $response2->assertViewIs('patient.maternal');

        // User without maternal record gets redirected
        $anotherUser = User::create([
            'name' => 'No Record User',
            'email' => 'norecord@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);
        $response3 = $this->actingAs($anotherUser)->get('/maternal');
        $response3->assertRedirect('/dashboard');
        $response3->assertSessionHas('error');
    }

    /**
     * Test POST /maternal (admin adds a checkup)
     */
    public function test_admin_can_add_maternal_checkup(): void
    {
        $patient = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '1995-05-15',
            'gender' => 'Female',
            'phone' => '1234567890',
            'email' => 'jane@example.com',
            'address' => 'Test Address',
            'registration_type' => 'Maternal',
        ]);

        $maternalRecord = MaternalRecord::create([
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01',
            'edd' => '2026-10-08',
            'gravida' => 1,
            'para' => 0,
        ]);

        $checkupData = [
            'weight_kg' => 65.5,
            'bp' => '120/80',
            'fetal_heart_rate' => 140,
            'notes' => 'Healthy fetal heartbeat detected.',
        ];

        // Accessing as regular user should NOT allow POST
        $responseUser = $this->actingAs($this->regularUser)->post('/maternal?id=' . $patient->id, $checkupData);
        // PageController checks if user is admin. If not, it just falls through to returning a view or not creating
        // Let's verify it didn't create a checkup
        $this->assertDatabaseCount('maternal_checkups', 0);

        // Accessing as admin should succeed
        $responseAdmin = $this->actingAs($this->adminUser)->post('/maternal?id=' . $patient->id, $checkupData);
        $responseAdmin->assertRedirect();
        $responseAdmin->assertSessionHas('success');

        $this->assertDatabaseHas('maternal_checkups', [
            'maternal_record_id' => $maternalRecord->id,
            'weight_kg' => 65.5,
            'bp' => '120/80',
            'fetal_heart_rate' => 140,
            'visit_number' => 1,
        ]);
    }

    /**
     * Test POST /growth (admin adds growth measurement)
     */
    public function test_admin_can_add_growth_measurement(): void
    {
        // Mother patient
        $mother = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '1995-05-15',
            'gender' => 'Female',
            'phone' => '1234567890',
            'email' => 'jane@example.com',
            'address' => 'Test Address',
            'registration_type' => 'Maternal',
            'user_id' => $this->regularUser->id,
        ]);

        // Child patient
        $child = Patient::create([
            'first_name' => 'Baby',
            'last_name' => 'Doe',
            'dob' => Carbon::now()->subMonths(6)->format('Y-m-d'), // 6 months old
            'gender' => 'Male',
            'phone' => '1234567890',
            'email' => null,
            'address' => 'Test Address',
            'registration_type' => 'Child',
        ]);

        $childRecord = ChildRecord::create([
            'patient_id' => $child->id,
            'mother_id' => $mother->id,
            'birth_weight_kg' => 3.0,
            'birth_height_cm' => 50.0,
        ]);

        $growthData = [
            'weight_kg' => 7.5,
            'height_cm' => 68.0,
        ];

        // Admin adds growth measurement
        $response = $this->actingAs($this->adminUser)->post('/growth?id=' . $child->id, $growthData);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('growth_measurements', [
            'child_record_id' => $childRecord->id,
            'weight_kg' => 7.5,
            'height_cm' => 68.0,
            'age_months' => 6,
        ]);

        // Regular user (mother) can view child's growth measurements
        $responseUser = $this->actingAs($this->regularUser)->get('/growth');
        $responseUser->assertStatus(200);
        $responseUser->assertViewIs('patient.growth');
        $responseUser->assertViewHas('patient');
    }

    /**
     * Test POST /immunization (admin updates immunization status)
     */
    public function test_admin_can_update_immunization_status(): void
    {
        $child = Patient::create([
            'first_name' => 'Baby',
            'last_name' => 'Doe',
            'dob' => '2026-01-01',
            'gender' => 'Male',
            'phone' => '1234567890',
            'email' => null,
            'address' => 'Test Address',
            'registration_type' => 'Child',
        ]);

        $childRecord = ChildRecord::create([
            'patient_id' => $child->id,
            'birth_weight_kg' => 3.0,
            'birth_height_cm' => 50.0,
        ]);

        $immunization = Immunization::create([
            'child_record_id' => $childRecord->id,
            'vaccine_name' => 'Pentavalent (DPT-HepB-Hib)',
            'dose_number' => 1,
            'scheduled_date' => '2026-02-15',
            'status' => 'Scheduled',
        ]);

        $response = $this->actingAs($this->adminUser)->post('/immunization?id=' . $child->id, [
            'immunization_id' => $immunization->id,
            'remarks' => 'Administered at health center.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('immunizations', [
            'id' => $immunization->id,
            'status' => 'Given',
            'remarks' => 'Administered at health center.',
            'administered_by' => 'Admin Midwife',
        ]);
    }

    /**
     * Test POST /sms (admin sends SMS successfully via gateway)
     */
    public function test_admin_can_send_sms_success(): void
    {
        Http::fake([
            '*/message' => Http::response(['success' => true], 200),
            '*/health' => Http::response(['status' => 'pass'], 200),
        ]);

        $patient = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '1995-05-15',
            'gender' => 'Female',
            'phone' => '09171234567',
            'email' => 'jane@example.com',
            'address' => 'Test Address',
            'registration_type' => 'Maternal',
        ]);

        $response = $this->actingAs($this->adminUser)->post('/sms', [
            'patient_id' => $patient->id,
            'message' => 'Your checkup schedule is tomorrow.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('sms_messages', [
            'patient_id' => $patient->id,
            'phone_number' => '09171234567',
            'message' => 'Your checkup schedule is tomorrow.',
            'status' => 'Sent',
            'type' => 'Manual',
        ]);
    }

    /**
     * Test POST /sms (admin sends SMS which fails at gateway)
     */
    public function test_admin_can_send_sms_failure(): void
    {
        Http::fake([
            '*/message' => Http::response(['success' => false, 'message' => 'Invalid credentials'], 401),
            '*/health' => Http::response(['status' => 'pass'], 200),
        ]);

        $patient = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '1995-05-15',
            'gender' => 'Female',
            'phone' => '09171234567',
            'email' => 'jane@example.com',
            'address' => 'Test Address',
            'registration_type' => 'Maternal',
        ]);

        $response = $this->actingAs($this->adminUser)->post('/sms', [
            'patient_id' => $patient->id,
            'message' => 'Your checkup schedule is tomorrow.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('sms_messages', [
            'patient_id' => $patient->id,
            'phone_number' => '09171234567',
            'message' => 'Your checkup schedule is tomorrow.',
            'status' => 'Failed',
            'type' => 'Manual',
        ]);
    }

    /**
     * Test POST /sms/settings (admin updates SMS settings)
     */
    public function test_admin_can_update_sms_settings(): void
    {
        // Clean up setting file if any
        $settingsPath = storage_path('app/sms_settings.json');
        if (file_exists($settingsPath)) {
            unlink($settingsPath);
        }

        $response = $this->actingAs($this->adminUser)->post('/sms/settings', [
            'url' => 'http://192.168.1.50:8080',
            'username' => 'test-user',
            'password' => 'test-pass',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFileExists($settingsPath);

        $raw = file_get_contents($settingsPath);
        // Credentials must not be stored in plaintext on disk.
        $this->assertStringNotContainsString('test-pass', $raw);
        $this->assertStringNotContainsString('"password"', $raw);

        $settings = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($raw), true);
        $this->assertEquals('http://192.168.1.50:8080', $settings['url']);
        $this->assertEquals('test-user', $settings['username']);
        $this->assertEquals('test-pass', $settings['password']);

        if (file_exists($settingsPath)) {
            unlink($settingsPath);
        }
    }

    public function test_sms_settings_legacy_plaintext_is_migrated_to_encrypted(): void
    {
        $settingsPath = storage_path('app/sms_settings.json');
        if (file_exists($settingsPath)) {
            unlink($settingsPath);
        }

        // Simulate a pre-encryption plaintext settings file.
        file_put_contents($settingsPath, json_encode([
            'url' => 'http://192.168.1.50:8080',
            'username' => 'legacy-user',
            'password' => 'legacy-pass',
        ]));

        $settings = $this->app->make(\App\Services\SmsService::class)->getSettings();

        $this->assertEquals('http://192.168.1.50:8080', $settings['url']);
        $this->assertEquals('legacy-user', $settings['username']);
        $this->assertEquals('legacy-pass', $settings['password']);

        // Reading a legacy file should rewrite it encrypted at rest.
        $raw = file_get_contents($settingsPath);
        $this->assertStringNotContainsString('legacy-pass', $raw);
        $migrated = json_decode(\Illuminate\Support\Facades\Crypt::decryptString($raw), true);
        $this->assertEquals('legacy-pass', $migrated['password']);

        if (file_exists($settingsPath)) {
            unlink($settingsPath);
        }
    }

    /**
     * Test GET /sms/status (admin checks SMS gateway connection status)
     */
    public function test_admin_can_check_sms_status(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'pass'], 200),
        ]);

        $response = $this->actingAs($this->adminUser)->get('/sms/status');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'status' => 'online',
        ]);
    }

    /**
     * Test messaging: sending chat messages
     */
    public function test_messaging_flows(): void
    {
        // 1. Admin sends message to User
        $response = $this->actingAs($this->adminUser)->post('/messaging', [
            'message' => 'Hello Patient, how are you feeling today?',
            'receiver_id' => $this->regularUser->id,
        ]);
        $response->assertRedirect(route('messaging', ['chat_user_id' => $this->regularUser->id]));

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->adminUser->id,
            'receiver_id' => $this->regularUser->id,
            'message' => 'Hello Patient, how are you feeling today?',
            'is_read' => false,
        ]);

        // 2. User views messaging page -> message should be marked as read
        $responseUserGet = $this->actingAs($this->regularUser)->get('/messaging');
        $responseUserGet->assertStatus(200);
        $responseUserGet->assertViewIs('patient.messaging');

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->adminUser->id,
            'receiver_id' => $this->regularUser->id,
            'is_read' => true,
        ]);

        // 3. User replies to Admin
        $responseUserPost = $this->actingAs($this->regularUser)->post('/messaging', [
            'message' => 'I feel great, thank you!',
        ]);
        $responseUserPost->assertRedirect(route('messaging'));

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->regularUser->id,
            'receiver_id' => $this->adminUser->id,
            'message' => 'I feel great, thank you!',
            'is_read' => false,
        ]);

        // 4. Admin views messaging page -> message should be marked as read
        $responseAdminGet = $this->actingAs($this->adminUser)->get('/messaging?chat_user_id=' . $this->regularUser->id);
        $responseAdminGet->assertStatus(200);
        $responseAdminGet->assertViewIs('messaging');

        $this->assertDatabaseHas('chat_messages', [
            'sender_id' => $this->regularUser->id,
            'receiver_id' => $this->adminUser->id,
            'is_read' => true,
        ]);
    }

    /**
     * Test AJAX messaging flows return JSON responses.
     */
    public function test_messaging_ajax_flows(): void
    {
        // 1. Admin sends message to User via AJAX
        $response = $this->actingAs($this->adminUser)
            ->postJson('/messaging', [
                'message' => 'Hello Patient, how are you feeling today?',
                'receiver_id' => $this->regularUser->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => [
                'sender_id' => $this->adminUser->id,
                'receiver_id' => $this->regularUser->id,
                'message' => 'Hello Patient, how are you feeling today?',
            ],
        ]);

        // 2. User replies to Admin via AJAX
        $responseUser = $this->actingAs($this->regularUser)
            ->postJson('/messaging', [
                'message' => 'I feel great, thank you!',
            ]);

        $responseUser->assertStatus(200);
        $responseUser->assertJson([
            'success' => true,
            'message' => [
                'sender_id' => $this->regularUser->id,
                'receiver_id' => $this->adminUser->id,
                'message' => 'I feel great, thank you!',
            ],
        ]);
    }

    /**
     * Test chat messaging with image attachment.
     */
    public function test_message_with_image_upload(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->create('vaccine_card.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($this->adminUser)
            ->postJson('/messaging', [
                'message' => 'Here is the vaccination card',
                'receiver_id' => $this->regularUser->id,
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $message = ChatMessage::latest()->first();
        $this->assertNotNull($message->attachment_path);
        $this->assertEquals('vaccine_card.jpg', $message->attachment_name);
        $this->assertStringStartsWith('image/', $message->attachment_type);
        $this->assertTrue($message->isImage());
        $this->assertFalse($message->isVideo());
        $this->assertFalse($message->isDocument());

        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($message->attachment_path);
    }

    /**
     * Test chat messaging with document attachment.
     */
    public function test_message_with_document_upload(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->create('medical_record.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->regularUser)
            ->postJson('/messaging', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $message = ChatMessage::latest()->first();
        $this->assertNotNull($message->attachment_path);
        $this->assertEquals('medical_record.pdf', $message->attachment_name);
        $this->assertEquals('application/pdf', $message->attachment_type);
        $this->assertFalse($message->isImage());
        $this->assertFalse($message->isVideo());
        $this->assertTrue($message->isDocument());

        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($message->attachment_path);
    }

    /**
     * Test chat messaging with a 15MB video attachment.
     */
    public function test_message_with_large_video_upload(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $file = \Illuminate\Http\UploadedFile::fake()->create('test_video.mp4', 15 * 1024, 'video/mp4');

        $response = $this->actingAs($this->adminUser)
            ->postJson('/messaging', [
                'message' => 'Here is a video',
                'receiver_id' => $this->regularUser->id,
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $message = ChatMessage::latest()->first();
        $this->assertNotNull($message->attachment_path);
        $this->assertEquals('test_video.mp4', $message->attachment_name);
        $this->assertTrue($message->isVideo());

        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($message->attachment_path);
    }

    /**
     * Test admin can access admin panel.
     */
    public function test_admin_can_access_admin_panel(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');
        $response->assertStatus(200);
        $response->assertViewIs('admin');
        $response->assertViewHas('users');
    }

    /**
     * Test non-admin gets 403 on admin panel.
     */
    public function test_user_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->regularUser)->get('/admin');
        $response->assertStatus(403);
    }

    /**
     * Test broadcasting authorization for the online presence channel.
     */
    public function test_online_presence_channel_authorization(): void
    {
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb' => [
                'driver' => 'reverb',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'app_id' => 'test-app-id',
                'options' => [
                    'host' => 'localhost',
                    'port' => 8080,
                    'scheme' => 'http',
                ],
            ],
        ]);

        \Illuminate\Support\Facades\Broadcast::forgetDrivers();
        require base_path('routes/channels.php');

        // 1. Unauthorized guest cannot authenticate
        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'presence-online',
            'socket_id' => '1234.1234',
        ]);
        $response->assertStatus(403);

        // 2. Authenticated user (admin/midwife) can authorize
        $responseAdmin = $this->actingAs($this->adminUser)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'presence-online',
                'socket_id' => '1234.1234',
            ]);
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertJsonStructure([
            'auth',
            'channel_data',
        ]);

        // 3. Authenticated regular user (patient) can authorize
        $responseUser = $this->actingAs($this->regularUser)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'presence-online',
                'socket_id' => '1234.1234',
            ]);
        $responseUser->assertStatus(200);
        $responseUser->assertJsonStructure([
            'auth',
            'channel_data',
        ]);
    }
}
