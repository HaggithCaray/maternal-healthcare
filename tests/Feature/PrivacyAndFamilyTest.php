<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivacyAndFamilyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $motherUser;
    protected Patient $mother;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-20 10:00:00');

        $this->adminUser = User::create(['name' => 'Midwife Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin']);
        $this->motherUser = User::create(['name' => 'Joy Bautista', 'email' => 'joy@example.com', 'password' => Hash::make('secret123'), 'role' => 'user']);
        $this->mother = $this->patient(['user_id' => $this->motherUser->id, 'first_name' => 'Joy', 'dob' => '1995-03-03', 'gender' => 'Female', 'registration_type' => 'Maternal']);
    }

    protected function patient(array $attributes): Patient
    {
        return Patient::create($attributes + [
            'last_name' => 'Bautista', 'phone' => '09175550000', 'address' => 'Purok 4', 'barangay' => 'Bicao',
            'emergency_contact_name' => 'Ben', 'emergency_contact_phone' => '0922', 'status' => 'Active',
        ]);
    }

    protected function child(string $firstName, string $dob, ?Patient $mother = null): Patient
    {
        $child = $this->patient(['first_name' => $firstName, 'dob' => $dob, 'gender' => 'Male', 'registration_type' => 'Child']);
        $record = ChildRecord::create(['patient_id' => $child->id, 'mother_id' => ($mother ?? $this->mother)->id]);
        foreach (['BCG', 'Hepatitis B'] as $vaccine) {
            Immunization::create(['child_record_id' => $record->id, 'vaccine_name' => $vaccine, 'dose_number' => 1, 'scheduled_date' => $dob, 'status' => 'Scheduled']);
        }

        return $child;
    }

    // --- #1 Patient data is not kept on the device ------------------------------------

    public function test_signed_in_pages_tell_the_browser_not_to_store_them(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/records');

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_chat_attachments_are_not_stored_by_the_browser(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('attachments/lab.pdf', 'pdf');
        $message = ChatMessage::create([
            'sender_id' => $this->adminUser->id, 'receiver_id' => $this->motherUser->id, 'message' => 'Result',
            'attachment_path' => 'attachments/lab.pdf', 'attachment_name' => 'lab.pdf', 'attachment_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($this->motherUser)->get(route('messaging.attachment', $message));

        $response->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_offline_page_copies_are_cleared_at_logout_and_on_the_login_page(): void
    {
        $this->actingAs($this->adminUser)->get('/dashboard')
            ->assertSee('id="logout-form"', false)
            ->assertSee("caches.delete('maternal-health-pages')", false);

        auth()->logout();
        $this->get('/')->assertSee("caches.delete('maternal-health-pages')", false);
    }

    public function test_service_worker_only_keeps_the_registration_page_offline(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("const OFFLINE_PAGES = ['/register'];", $sw);
        $this->assertStringContainsString("const PAGES_CACHE = 'maternal-health-pages';", $sw);
        // Older caches (which held patient pages) are removed when this version activates.
        $this->assertStringContainsString("key !== CACHE_NAME && key !== PAGES_CACHE", $sw);
    }

    // --- #2 Mothers can see every child -----------------------------------------------

    public function test_mother_can_switch_between_her_children(): void
    {
        $older = $this->child('Nico', '2025-01-10');
        $younger = $this->child('Mia', '2026-05-01');

        foreach (['growth', 'immunization'] as $page) {
            $this->actingAs($this->motherUser)->get("/{$page}")
                ->assertOk()
                ->assertViewHas('patient', fn ($p) => $p->is($older))
                ->assertSee('aria-label="Choose a child"', false)
                ->assertSee('Mia');

            $this->actingAs($this->motherUser)->get("/{$page}?id={$younger->id}")
                ->assertOk()
                ->assertViewHas('patient', fn ($p) => $p->is($younger));
        }
    }

    public function test_mother_cannot_open_other_children_by_id(): void
    {
        $this->child('Nico', '2025-01-10');
        $otherMother = $this->patient(['first_name' => 'Ana', 'dob' => '1990-01-01', 'gender' => 'Female', 'registration_type' => 'Maternal']);
        $stranger = $this->child('Leo', '2025-06-06', $otherMother);

        $this->actingAs($this->motherUser)->get("/growth?id={$stranger->id}")->assertNotFound();
        $this->actingAs($this->motherUser)->get("/immunization?id={$stranger->id}")->assertNotFound();
        // Her own maternal record is not a child record either.
        $this->actingAs($this->motherUser)->get("/growth?id={$this->mother->id}")->assertNotFound();
    }

    public function test_no_switcher_for_a_single_child(): void
    {
        $this->child('Nico', '2025-01-10');

        $this->actingAs($this->motherUser)->get('/growth')->assertOk()->assertDontSee('aria-label="Choose a child"', false);
    }

    // --- #3 "Given at birth" checkboxes keep the vaccine record in step --------------

    protected function editChild(Patient $child, array $birthDoses): void
    {
        $this->actingAs($this->adminUser)->put("/patients/{$child->id}", [
            'first_name' => $child->first_name, 'last_name' => 'Bautista', 'dob' => $child->dob, 'gender' => 'Male',
            'phone' => '09175550000', 'address' => 'Purok 4', 'emergency_contact_name' => 'Ben', 'emergency_contact_phone' => '0922',
            'status' => 'Active', 'birth_type' => 'Single', 'delivery_type' => 'Normal',
        ] + $birthDoses)->assertRedirect(route('records'));
    }

    public function test_ticking_birth_dose_on_edit_marks_the_vaccine_given_and_unticking_reverts_it(): void
    {
        $child = $this->child('Nico', '2026-07-01');

        $this->editChild($child, ['has_bcg_at_birth' => '1']);
        $bcg = Immunization::where('vaccine_name', 'BCG')->sole();
        $this->assertSame('Given', $bcg->status);
        $this->assertSame('2026-07-01', Carbon::parse($bcg->given_date)->toDateString());
        $this->assertSame('Scheduled', Immunization::where('vaccine_name', 'Hepatitis B')->sole()->status);

        $this->editChild($child, []);
        $bcg->refresh();
        $this->assertSame('Scheduled', $bcg->status);
        $this->assertNull($bcg->given_date);
    }

    public function test_unticking_does_not_undo_a_dose_given_later_at_the_health_station(): void
    {
        $child = $this->child('Nico', '2026-07-01');
        Immunization::where('vaccine_name', 'BCG')->update([
            'status' => 'Given', 'given_date' => '2026-07-15', 'administered_by' => 'Midwife Rosa', 'remarks' => 'Regular schedule dose',
        ]);

        $this->editChild($child, []);

        $bcg = Immunization::where('vaccine_name', 'BCG')->sole();
        $this->assertSame('Given', $bcg->status);
        $this->assertSame('Midwife Rosa', $bcg->administered_by);
    }
}
