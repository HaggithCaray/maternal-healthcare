<?php

namespace Tests\Feature;

use App\Models\ChildRecord;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\SmsMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * A child linked to a registered mother uses her current contact details; an unlinked child
 * needs a parent or guardian's name, phone and address.
 */
class ChildContactTest extends TestCase
{
    use RefreshDatabase;

    protected User $midwife;

    protected Patient $mother;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-02 10:00:00');

        $this->midwife = User::create(['name' => 'Midwife Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin']);
        $this->mother = Patient::create([
            'first_name' => 'Ana', 'last_name' => 'Reyes', 'dob' => '1998-04-12', 'gender' => 'Female',
            'phone' => '09171111111', 'address' => 'Purok 2, Bicao',
            'emergency_contact_name' => 'Pedro Reyes', 'emergency_contact_phone' => '09172222222',
            'registration_type' => 'Maternal', 'barangay' => 'Bicao', 'status' => 'Active',
        ]);
        MaternalRecord::create(['patient_id' => $this->mother->id]);
    }

    /** What the child form sends: no contact details when a mother is linked. */
    protected function childForm(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Nico', 'last_name' => 'Reyes', 'dob' => '2026-09-30', 'gender' => 'Male',
            'registration_type' => 'Child', 'mother_id' => $this->mother->id,
        ], $overrides);
    }

    protected function child(): Patient
    {
        return Patient::where('registration_type', 'Child')->sole();
    }

    public function test_a_child_linked_to_its_mother_needs_no_contact_details_and_shows_hers(): void
    {
        $this->actingAs($this->midwife)->post('/register', $this->childForm())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('records'));

        $child = $this->child();
        foreach (Patient::CONTACT_FIELDS as $field) {
            $this->assertNull($child->getRawOriginal($field), "{$field} should not be copied");
        }
        $this->assertSame('09171111111', $child->phone);
        $this->assertSame('Purok 2, Bicao', $child->address);
        $this->assertSame('Pedro Reyes', $child->emergency_contact_name);
        $this->assertSame('09172222222', $child->emergency_contact_phone);
    }

    public function test_a_linked_child_follows_the_mothers_current_details(): void
    {
        $this->actingAs($this->midwife)->post('/register', $this->childForm());

        $this->mother->update(['phone' => '09179999999', 'address' => 'Purok 7, Bicao']);

        $child = $this->child()->fresh();
        $this->assertSame('09179999999', $child->phone);
        $this->assertSame('Purok 7, Bicao', $child->address);
    }

    public function test_contact_details_sent_for_a_linked_child_are_not_kept(): void
    {
        $this->actingAs($this->midwife)->post('/register', $this->childForm([
            'phone' => '09990000000', 'address' => 'Somewhere else', 'occupation' => 'n/a',
        ]))->assertSessionHasNoErrors();

        $child = $this->child();
        $this->assertNull($child->getRawOriginal('phone'));
        $this->assertNull($child->occupation);
        $this->assertSame('09171111111', $child->phone);
    }

    public function test_a_child_without_a_registered_mother_needs_a_guardian_name_phone_and_address(): void
    {
        $this->actingAs($this->midwife)->post('/register', $this->childForm(['mother_id' => '']))
            ->assertSessionHasErrors(['phone', 'address', 'emergency_contact_name'])
            ->assertSessionDoesntHaveErrors('emergency_contact_phone');

        $this->actingAs($this->midwife)->post('/register', $this->childForm([
            'mother_id' => '', 'emergency_contact_name' => 'Lola Cora', 'phone' => '09173333333', 'address' => 'Purok 4',
        ]))->assertSessionHasNoErrors();

        $child = $this->child();
        $this->assertSame('09173333333', $child->phone);
        $this->assertSame('Lola Cora', $child->emergency_contact_name);
        $this->assertNull($child->childRecord->mother_id);
    }

    public function test_a_mother_still_gives_all_her_contact_details(): void
    {
        $this->actingAs($this->midwife)->post('/register', [
            'first_name' => 'Bea', 'last_name' => 'Cruz', 'dob' => '1995-01-01', 'gender' => 'Female',
            'registration_type' => 'Maternal', 'phone' => '09174444444', 'address' => 'Purok 1',
            'emergency_contact_name' => 'Jose Cruz',
        ])->assertSessionHasErrors('emergency_contact_phone');

        $this->assertSame(1, Patient::count());
    }

    public function test_an_offline_child_registration_follows_the_mother_too(): void
    {
        $this->actingAs($this->midwife)->postJson('/api/sync/batch', ['items' => [[
            'id' => 1, 'uuid' => '34343434-3434-4434-8434-343434343434', 'type' => 'patient_registration',
            'data' => $this->childForm(),
        ]]])->assertJson(['success' => true]);

        $this->assertSame('09171111111', $this->child()->phone);
    }

    public function test_an_sms_to_a_linked_child_goes_to_the_mothers_phone(): void
    {
        Http::fake(['*' => Http::response(['status' => 'pass'], 200)]);
        $this->actingAs($this->midwife)->post('/register', $this->childForm());

        $this->actingAs($this->midwife)->post('/sms', ['patient_id' => $this->child()->id, 'message' => 'Vaccine day is Thursday.']);

        $this->assertSame('09171111111', SmsMessage::sole()->phone_number);
    }

    // --- Edit Patient -----------------------------------------------------------------------

    protected function editForm(array $overrides = []): array
    {
        return array_merge($this->childForm(), ['status' => 'Active'], $overrides);
    }

    public function test_editing_a_linked_child_shows_the_mothers_details_instead_of_fields(): void
    {
        $this->actingAs($this->midwife)->post('/register', $this->childForm());
        $child = $this->child();

        $this->actingAs($this->midwife)->get("/patients/{$child->id}/edit")
            ->assertOk()
            ->assertSee("uses the linked mother's address, phone and emergency contact", false)
            ->assertSee("Edit Ana's record", false)
            ->assertSee('class="grid grid-cols-1 md:grid-cols-2 gap-md hidden" id="contactFields"', false)
            ->assertDontSee('name="occupation"', false);

        $this->actingAs($this->midwife)->put("/patients/{$child->id}", $this->editForm())
            ->assertSessionHasNoErrors();
        $this->assertSame('09171111111', $child->fresh()->phone);
    }

    public function test_linking_a_mother_on_edit_drops_the_childs_own_copy(): void
    {
        $child = Patient::create([
            'first_name' => 'Nico', 'last_name' => 'Reyes', 'dob' => '2026-09-30', 'gender' => 'Male',
            'phone' => '09170000000', 'address' => 'Old copy', 'emergency_contact_name' => 'Old', 'emergency_contact_phone' => '0917',
            'registration_type' => 'Child', 'barangay' => 'Bicao', 'status' => 'Active',
        ]);
        ChildRecord::create(['patient_id' => $child->id]);

        $this->actingAs($this->midwife)->put("/patients/{$child->id}", $this->editForm())
            ->assertSessionHasNoErrors();

        $child->refresh();
        $this->assertNull($child->getRawOriginal('phone'));
        $this->assertSame($this->mother->id, $child->childRecord->mother_id);
        $this->assertSame('09171111111', $child->phone);
    }

    public function test_unlinking_the_mother_on_edit_asks_for_a_guardian(): void
    {
        $this->actingAs($this->midwife)->post('/register', $this->childForm());
        $child = $this->child();

        $this->actingAs($this->midwife)->put("/patients/{$child->id}", $this->editForm(['mother_id' => '']))
            ->assertSessionHasErrors(['phone', 'address', 'emergency_contact_name']);
    }

    public function test_a_mothers_edit_still_requires_her_contact_details(): void
    {
        $this->actingAs($this->midwife)->put("/patients/{$this->mother->id}", [
            'first_name' => 'Ana', 'last_name' => 'Reyes', 'dob' => '1998-04-12', 'gender' => 'Female',
            'status' => 'Active', 'phone' => '09171111111', 'address' => 'Purok 2, Bicao', 'emergency_contact_name' => 'Pedro Reyes',
        ])->assertSessionHasErrors('emergency_contact_phone');
    }
}
