<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Registration form validation: required fields gate each step, number fields refuse
 * text, and the shared PatientRegistration rules (also used by Edit Patient and offline
 * sync) reject implausible names, phone numbers, dates and obstetric history.
 */
class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Midwife Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin',
        ]);
    }

    protected function maternalForm(array $overrides = []): array
    {
        return array_merge([
            'registration_type' => 'Maternal',
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'dob' => '1998-08-10',
            'gender' => 'Female',
            'phone' => '09123456789',
            'address' => 'Purok 1, Bicao',
            'emergency_contact_name' => 'Juan Santos',
            'emergency_contact_phone' => '09987654321',
            'gravida' => 2,
            'para' => 1,
        ], $overrides);
    }

    // --- Names -----------------------------------------------------------------------------

    public function test_names_with_digits_are_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['first_name' => 'Maria2']))
            ->assertSessionHasErrors('first_name');

        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['last_name' => 'Santos 123']))
            ->assertSessionHasErrors('last_name');
    }

    public function test_names_with_spaces_hyphens_periods_and_apostrophes_are_accepted(): void
    {
        foreach (['Maria Dela Cruz', "O'Brien", 'Jean-Luc', 'Ana-Marie Jr.'] as $name) {
            $this->actingAs($this->adminUser)
                ->post('/register', $this->maternalForm(['first_name' => $name]))
                ->assertSessionDoesntHaveErrors('first_name');
        }
    }

    // --- Phone numbers ---------------------------------------------------------------------

    public function test_phone_numbers_with_letters_are_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['phone' => '0917CallNow']))
            ->assertSessionHasErrors('phone');

        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['emergency_contact_phone' => 'number nine']))
            ->assertSessionHasErrors('emergency_contact_phone');
    }

    public function test_too_short_phone_numbers_are_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['phone' => '0917']))
            ->assertSessionHasErrors('phone');
    }

    public function test_philippine_and_separated_phone_formats_are_accepted(): void
    {
        foreach (['09171234567', '0917 123 4567', '+639171234567', '0917-123-4567', '(038) 1234567'] as $phone) {
            $this->actingAs($this->adminUser)
                ->post('/register', $this->maternalForm(['phone' => $phone]))
                ->assertSessionDoesntHaveErrors('phone');
        }
    }

    // --- Dates ------------------------------------------------------------------------------

    public function test_dob_before_1900_is_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['dob' => '1899-12-31']))
            ->assertSessionHasErrors('dob');
    }

    public function test_future_dob_is_still_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['dob' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('dob');
    }

    // --- Obstetric history -----------------------------------------------------------------

    public function test_more_births_than_pregnancies_is_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['gravida' => 2, 'para' => 3]))
            ->assertSessionHasErrors('para');
    }

    public function test_para_without_gravida_is_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['gravida' => null, 'para' => 1]))
            ->assertSessionHasErrors('gravida');
    }

    public function test_decimal_gravida_or_para_is_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['gravida' => '1.5']))
            ->assertSessionHasErrors('gravida');
    }

    // --- PhilHealth ------------------------------------------------------------------------

    public function test_malformed_philhealth_number_is_rejected(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['philhealth_number' => 'ABC-123']))
            ->assertSessionHasErrors('philhealth_number');

        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['philhealth_number' => '12-345-67']))
            ->assertSessionHasErrors('philhealth_number');
    }

    public function test_valid_philhealth_number_is_accepted(): void
    {
        $this->actingAs($this->adminUser)
            ->post('/register', $this->maternalForm(['philhealth_number' => '12-345678901-2']))
            ->assertSessionDoesntHaveErrors('philhealth_number');
    }

    // --- Edit Patient uses the same rules ---------------------------------------------------

    public function test_editing_a_patient_cannot_store_an_implausible_phone(): void
    {
        $this->actingAs($this->adminUser)->post('/register', $this->maternalForm())
            ->assertSessionHasNoErrors();

        $patient = \App\Models\Patient::where('first_name', 'Maria')->first();

        $this->actingAs($this->adminUser)->put("/patients/{$patient->id}", [
            'first_name' => 'Maria', 'last_name' => 'Dela Cruz', 'dob' => '1998-08-10', 'gender' => 'Female',
            'phone' => 'not a phone', 'address' => 'Purok 1, Bicao',
            'emergency_contact_name' => 'Juan Santos', 'emergency_contact_phone' => '09987654321',
            'status' => 'Active',
        ])->assertSessionHasErrors('phone');
    }
}
