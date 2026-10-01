<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Patient;
use App\Models\Immunization;
use App\Models\ChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login form is displayed.
     */
    public function test_login_form_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Test login endpoint throttles repeated attempts for the same email+IP.
     */
    public function test_login_is_rate_limited_after_repeated_attempts(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'throttle@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/', [
                'email' => 'throttle@example.com',
                'password' => 'wrong-password',
                'role' => 'user',
            ])->assertSessionHasErrors('email');
        }

        // Blocked even with the right password, and sent back to the form with a "please wait" message.
        $this->from('/')->post('/', [
            'email' => 'throttle@example.com',
            'password' => 'password123',
            'role' => 'user',
        ])->assertRedirect('/')->assertSessionHasErrors('throttle');

        $this->assertGuest();
    }

    /**
     * Test successful login as admin redirecting to dashboard.
     */
    public function test_admin_can_login_successfully(): void
    {
        $password = 'password123';
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $response = $this->post('/', [
            'email' => 'admin@example.com',
            'password' => $password,
            'role' => 'admin',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    /**
     * Test successful login as user redirecting to portal.
     */
    public function test_user_can_login_successfully(): void
    {
        $password = 'password123';
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make($password),
            'role' => 'user',
        ]);

        $response = $this->post('/', [
            'email' => 'user@example.com',
            'password' => $password,
            'role' => 'user',
        ]);

        $response->assertRedirect('/portal');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test login failure with incorrect credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $password = 'password123';
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make($password),
            'role' => 'user',
        ]);

        $response = $this->post('/', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
            'role' => 'user',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test login failure with incorrect role.
     */
    public function test_user_cannot_login_with_incorrect_role(): void
    {
        $password = 'password123';
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make($password),
            'role' => 'user',
        ]);

        // Attempting to log in as admin
        $response = $this->post('/', [
            'email' => 'user@example.com',
            'password' => $password,
            'role' => 'admin',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Test user logout.
     */
    public function test_user_can_logout(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test dashboard statistics and data display for admin.
     */
    public function test_admin_can_view_dashboard_with_statistics(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create some sample data
        // Maternal patient
        $maternalPatient = Patient::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'dob' => '1995-01-01',
            'gender' => 'Female',
            'phone' => '1234567890',
            'email' => 'jane@example.com',
            'address' => 'Test Address',
            'registration_type' => 'Maternal',
            'status' => 'Active',
        ]);

        // Child patient
        $childPatient = Patient::create([
            'first_name' => 'Baby',
            'last_name' => 'Doe',
            'dob' => '2026-01-01',
            'gender' => 'Male',
            'phone' => '1234567890',
            'email' => null,
            'address' => 'Test Address',
            'registration_type' => 'Child',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertViewHas('totalMothers', 1);
        $response->assertViewHas('totalChildren', 1);
        $response->assertViewHas('todayVaccinations', 0);
        $response->assertViewHas('unreadMessages', 0);
    }
}
