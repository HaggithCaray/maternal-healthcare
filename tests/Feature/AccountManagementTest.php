<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
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

    protected function login(string $email, string $password, string $role)
    {
        return $this->post('/', ['email' => $email, 'password' => $password, 'role' => $role]);
    }

    /**
     * A stored session for the user, as the database session driver (used in production) keeps them.
     */
    protected function fakeSession(User $user, string $id): void
    {
        config(['session.driver' => 'database']);

        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
    }

    // --- Admin page shows real accounts ----------------------------------------------

    public function test_admin_page_lists_real_accounts_not_sample_users(): void
    {
        $this->actingAs($this->adminUser)->get('/admin')
            ->assertOk()
            ->assertSee('maria@example.com')
            ->assertSee('admin@maternal.com')
            ->assertDontSee('maria.santos@bch.gov.ph')
            ->assertDontSee('Nurse Elena Reyes');
    }

    public function test_admin_can_filter_and_search_accounts(): void
    {
        $this->actingAs($this->adminUser)->get('/admin?role=user')
            ->assertSee('maria@example.com')
            ->assertDontSee('admin@maternal.com');

        $this->actingAs($this->adminUser)->get('/admin?search=admin@')
            ->assertSee('admin@maternal.com')
            ->assertDontSee('maria@example.com');
    }

    public function test_patients_cannot_open_admin_pages(): void
    {
        foreach (['/admin', '/admin/activity', '/admin/users/create', "/admin/users/{$this->adminUser->id}/edit"] as $url) {
            $this->actingAs($this->patientUser)->get($url)->assertStatus(403);
        }
        $this->actingAs($this->patientUser)->post("/admin/users/{$this->adminUser->id}/status")->assertStatus(403);
    }

    // --- Staff accounts ---------------------------------------------------------------

    public function test_admin_can_create_staff_account_that_can_sign_in(): void
    {
        $response = $this->actingAs($this->adminUser)->post('/admin/users', [
            'name' => 'Midwife Ana Reyes',
            'email' => 'ana@health.test',
        ]);

        $response->assertRedirect(route('admin'))->assertSessionHas('portal_credentials');
        $credentials = session('portal_credentials');
        $this->assertSame('admin', $credentials['role']);
        $this->assertDatabaseHas('users', ['email' => 'ana@health.test', 'role' => 'admin', 'is_active' => true]);

        auth()->logout();
        $this->login('ana@health.test', $credentials['password'], 'admin')->assertRedirect('/dashboard');
    }

    public function test_staff_email_must_be_unique(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/admin/users', ['name' => 'Copy', 'email' => 'maria@example.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_update_staff_details_but_not_patient_logins(): void
    {
        $staff = User::create(['name' => 'Old Name', 'email' => 'old@health.test', 'password' => 'x', 'role' => 'admin']);

        $this->actingAs($this->adminUser)
            ->put("/admin/users/{$staff->id}", ['name' => 'New Name', 'email' => 'new@health.test'])
            ->assertSessionHas('success');
        $this->assertSame('new@health.test', $staff->fresh()->email);

        $this->actingAs($this->adminUser)
            ->put("/admin/users/{$this->patientUser->id}", ['name' => 'Hacked', 'email' => 'x@example.com'])
            ->assertSessionHas('error');
        $this->assertSame('maria@example.com', $this->patientUser->fresh()->email);
    }

    public function test_reset_password_gives_new_password_and_signs_out_other_devices(): void
    {
        $this->fakeSession($this->patientUser, 'patient-phone-session');

        $this->actingAs($this->adminUser)
            ->post("/admin/users/{$this->patientUser->id}/password")
            ->assertSessionHas('portal_credentials');

        $newPassword = session('portal_credentials')['password'];
        $this->assertTrue(Hash::check($newPassword, $this->patientUser->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'patient-phone-session']);
    }

    // --- Deactivation -----------------------------------------------------------------

    public function test_deactivated_account_cannot_sign_in_and_is_signed_out(): void
    {
        $this->actingAs($this->adminUser)
            ->post("/admin/users/{$this->patientUser->id}/status")
            ->assertSessionHas('success');
        $this->assertFalse($this->patientUser->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'deactivate_account', 'model_id' => $this->patientUser->id]);

        auth()->logout();
        $this->login('maria@example.com', 'secret123', 'user')->assertSessionHasErrors('email');
        $this->assertGuest();

        // A session that was already open is ended on the next request.
        $this->actingAs($this->patientUser->fresh())->get('/portal')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_reactivated_account_can_sign_in_again(): void
    {
        $this->patientUser->update(['is_active' => false]);

        $this->actingAs($this->adminUser)->post("/admin/users/{$this->patientUser->id}/status");
        auth()->logout();

        $this->login('maria@example.com', 'secret123', 'user')->assertRedirect('/portal');
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $this->actingAs($this->adminUser)
            ->post("/admin/users/{$this->adminUser->id}/status")
            ->assertSessionHas('error');

        $this->assertTrue($this->adminUser->fresh()->is_active);
    }

    public function test_successful_login_records_last_sign_in(): void
    {
        $this->login('maria@example.com', 'secret123', 'user');

        $this->assertNotNull($this->patientUser->fresh()->last_login_at);
    }

    // --- Activity log -----------------------------------------------------------------

    public function test_activity_log_shows_real_entries_and_filters_by_user(): void
    {
        AuditLog::create(['user_id' => $this->adminUser->id, 'action' => 'create_patient', 'details' => []]);
        AuditLog::create(['user_id' => $this->patientUser->id, 'action' => 'view_patient_portal', 'details' => []]);

        $this->actingAs($this->adminUser)->get('/admin/activity')
            ->assertOk()
            ->assertSee('create patient')
            ->assertSee('view patient portal')
            ->assertDontSee('daily backup completed');

        $this->actingAs($this->adminUser)->get('/admin/activity?user=' . $this->patientUser->id)
            ->assertSee('view patient portal')
            ->assertDontSee('create patient');
    }

    // --- Changing your own password ---------------------------------------------------

    public function test_user_can_change_own_password(): void
    {
        $this->fakeSession($this->patientUser, 'old-tablet-session');

        $this->actingAs($this->patientUser)->put('/account/password', [
            'current_password' => 'secret123',
            'password' => 'newpass2026',
            'password_confirmation' => 'newpass2026',
        ])->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpass2026', $this->patientUser->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-tablet-session']);
    }

    public function test_changing_password_requires_the_current_one_and_a_strong_new_one(): void
    {
        $this->actingAs($this->patientUser)->put('/account/password', [
            'current_password' => 'wrong',
            'password' => 'newpass2026',
            'password_confirmation' => 'newpass2026',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($this->patientUser)->put('/account/password', [
            'current_password' => 'secret123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('secret123', $this->patientUser->fresh()->password));
    }

    // --- Patient login follows the patient record --------------------------------------

    public function test_changing_patient_email_updates_their_login(): void
    {
        $patient = Patient::create([
            'user_id' => $this->patientUser->id,
            'first_name' => 'Maria', 'last_name' => 'Santos', 'dob' => '1995-01-01', 'gender' => 'Female',
            'phone' => '09171234567', 'email' => 'maria@example.com', 'address' => 'Purok 1', 'barangay' => 'Bicao',
            'emergency_contact_name' => 'Juan', 'emergency_contact_phone' => '0922', 'registration_type' => 'Maternal', 'status' => 'Active',
        ]);

        $this->actingAs($this->adminUser)->put("/patients/{$patient->id}", [
            'first_name' => 'Maria', 'last_name' => 'Santos-Cruz', 'dob' => '1995-01-01', 'gender' => 'Female',
            'phone' => '09171234567', 'email' => 'maria.new@example.com', 'address' => 'Purok 1',
            'emergency_contact_name' => 'Juan', 'emergency_contact_phone' => '0922', 'status' => 'Active',
        ])->assertRedirect(route('records'));

        $user = $this->patientUser->fresh();
        $this->assertSame('maria.new@example.com', $user->email);
        $this->assertSame('Maria Santos-Cruz', $user->name);
    }

    public function test_patient_email_cannot_take_another_accounts_login(): void
    {
        $patient = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Cruz', 'dob' => '1995-01-01', 'gender' => 'Female',
            'phone' => '09171234567', 'address' => 'Purok 1', 'barangay' => 'Bicao',
            'emergency_contact_name' => 'Juan', 'emergency_contact_phone' => '0922', 'registration_type' => 'Maternal', 'status' => 'Active',
        ]);

        $this->actingAs($this->adminUser)->put("/patients/{$patient->id}", [
            'first_name' => 'Ana', 'last_name' => 'Cruz', 'dob' => '1995-01-01', 'gender' => 'Female',
            'phone' => '09171234567', 'email' => 'maria@example.com', 'address' => 'Purok 1',
            'emergency_contact_name' => 'Juan', 'emergency_contact_phone' => '0922', 'status' => 'Active',
        ])->assertSessionHasErrors('email');
    }
}
