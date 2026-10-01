<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChildRecord;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChildAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $midwife;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 10:00:00');

        $this->midwife = User::create(['name' => 'Midwife Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin']);
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
            'dob' => '2026-09-30',
            'gender' => 'Male',
            'registration_type' => 'Child',
        ], $overrides));
    }

    /**
     * @return array{0: Patient, 1: User} mother patient and her portal login
     */
    protected function motherWithLogin(): array
    {
        $login = User::create(['name' => 'Ana Reyes', 'email' => 'ana@example.com', 'password' => Hash::make('secret123'), 'role' => 'user']);
        $mother = Patient::create($this->patientData(['user_id' => $login->id, 'email' => 'ana@example.com', 'barangay' => 'Bicao', 'status' => 'Active']));
        MaternalRecord::create(['patient_id' => $mother->id]);

        return [$mother, $login];
    }

    protected function runCleanup(): void
    {
        (require database_path('migrations/2026_10_02_000000_remove_portal_logins_from_child_patients.php'))->up();
    }

    // --- New registrations ------------------------------------------------------------------

    public function test_registering_a_child_creates_no_login_even_with_an_email(): void
    {
        $this->actingAs($this->midwife)
            ->post('/register', $this->childData(['email' => 'baby.nico@example.com']))
            ->assertRedirect(route('records'))
            ->assertSessionMissing('portal_credentials');

        $child = Patient::where('registration_type', 'Child')->sole();
        $this->assertNull($child->user_id);
        $this->assertNull($child->email);
        $this->assertFalse(User::where('email', 'baby.nico@example.com')->exists());
    }

    public function test_a_child_registered_with_the_mothers_email_is_seen_through_the_mother_link(): void
    {
        [$mother, $login] = $this->motherWithLogin();

        $this->actingAs($this->midwife)->post('/register', $this->childData([
            'email' => 'ana@example.com',
            'mother_id' => $mother->id,
        ]))->assertSessionHasNoErrors();

        $child = Patient::where('registration_type', 'Child')->sole();
        $this->assertNull($child->user_id);
        $this->assertSame($mother->id, $child->childRecord->mother_id);
        $this->assertSame(1, User::where('role', 'user')->count());

        // The mother still sees her baby in her portal.
        $this->actingAs($login)->get('/immunization')->assertOk()->assertSee('Nico');
    }

    public function test_an_offline_child_registration_creates_no_login(): void
    {
        $this->actingAs($this->midwife)->postJson('/api/sync/batch', ['items' => [[
            'id' => 1, 'uuid' => '12121212-1212-4212-8212-121212121212', 'type' => 'patient_registration',
            'data' => $this->childData(['email' => 'offline.baby@example.com']),
        ]]])->assertJson(['success' => true]);

        $this->assertNull(Patient::where('registration_type', 'Child')->sole()->user_id);
        $this->assertFalse(User::where('email', 'offline.baby@example.com')->exists());
    }

    public function test_a_child_cannot_be_given_a_portal_login(): void
    {
        $child = Patient::create($this->childData(['email' => 'legacy@example.com', 'barangay' => 'Bicao', 'status' => 'Active']));
        ChildRecord::create(['patient_id' => $child->id]);

        $this->actingAs($this->midwife)->post("/patients/{$child->id}/portal-password")
            ->assertSessionHas('error');

        $this->assertFalse(User::where('email', 'legacy@example.com')->exists());
        $this->assertNull($child->fresh()->user_id);
    }

    public function test_edit_patient_offers_no_login_for_a_child_and_saves_without_an_email(): void
    {
        [$mother] = $this->motherWithLogin();
        $child = Patient::create($this->childData(['barangay' => 'Bicao', 'status' => 'Active']));
        ChildRecord::create(['patient_id' => $child->id, 'mother_id' => $mother->id]);

        $this->actingAs($this->midwife)->get("/patients/{$child->id}/edit")
            ->assertOk()
            ->assertSee("Children don't have their own login", false)
            ->assertSee('sees this child in her portal')
            ->assertDontSee('Create Portal Account')
            ->assertDontSee('name="email"', false);

        $this->actingAs($this->midwife)->put("/patients/{$child->id}", array_merge(
            $this->childData(), ['status' => 'Active', 'mother_id' => $mother->id]
        ))->assertSessionHasNoErrors();
    }

    public function test_registration_form_hides_the_email_for_a_child(): void
    {
        // The email (her portal login) is only in the mother's part of the form.
        $html = $this->actingAs($this->midwife)->get('/register')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<div class="flex flex-col gap-xs" data-maternal-only>\s*<label[^>]*>Email Address<\/label>/', $html);
    }

    // --- Cleanup of logins made before this fix -----------------------------------------------

    public function test_cleanup_deletes_an_unused_login_made_for_a_child(): void
    {
        $login = User::create(['name' => 'Nico Reyes', 'email' => 'nico@example.com', 'password' => Hash::make('x'), 'role' => 'user']);
        $child = Patient::create($this->childData(['user_id' => $login->id, 'barangay' => 'Bicao', 'status' => 'Active']));
        ChildRecord::create(['patient_id' => $child->id]);

        $this->runCleanup();

        $this->assertNull($child->fresh()->user_id);
        $this->assertNull(User::find($login->id));
    }

    public function test_cleanup_links_a_child_that_shared_the_mothers_login_to_her(): void
    {
        [$mother, $login] = $this->motherWithLogin();
        $child = Patient::create($this->childData(['user_id' => $login->id, 'barangay' => 'Bicao', 'status' => 'Active']));
        ChildRecord::create(['patient_id' => $child->id]);

        $this->runCleanup();

        $this->assertNull($child->fresh()->user_id);
        $this->assertSame($mother->id, $child->childRecord->fresh()->mother_id);
        $this->assertTrue((bool) $login->fresh()->is_active);
        $this->assertSame($login->id, $mother->fresh()->user_id);
    }

    public function test_cleanup_deactivates_a_child_login_that_was_used(): void
    {
        $login = User::create(['name' => 'Nico Reyes', 'email' => 'nico@example.com', 'password' => Hash::make('x'), 'role' => 'user']);
        $child = Patient::create($this->childData(['user_id' => $login->id, 'barangay' => 'Bicao', 'status' => 'Active']));
        ChildRecord::create(['patient_id' => $child->id]);
        ChatMessage::create(['sender_id' => $login->id, 'receiver_id' => $this->midwife->id, 'message' => 'hello', 'is_read' => false]);

        $this->runCleanup();

        $this->assertNull($child->fresh()->user_id);
        $this->assertFalse((bool) $login->fresh()->is_active);
        $this->assertSame(1, ChatMessage::count());
    }

    public function test_a_login_left_over_from_a_child_is_not_in_the_chat_list(): void
    {
        [, $motherLogin] = $this->motherWithLogin();
        $orphan = User::create(['name' => 'Nico Reyes', 'email' => 'nico@example.com', 'password' => Hash::make('x'), 'role' => 'user', 'is_active' => false]);

        $this->actingAs($this->midwife)->get('/messaging')
            ->assertOk()
            ->assertSee($motherLogin->email)
            ->assertDontSee($orphan->email);
    }

    public function test_cleanup_never_touches_staff_accounts(): void
    {
        $child = Patient::create($this->childData(['user_id' => $this->midwife->id, 'barangay' => 'Bicao', 'status' => 'Active']));
        ChildRecord::create(['patient_id' => $child->id]);

        $this->runCleanup();

        $this->assertNull($child->fresh()->user_id);
        $this->assertTrue((bool) $this->midwife->fresh()->is_active);
    }
}
