<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\SmsMessage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pages show the records in the database, never sample names, dates or numbers.
 */
class RealContentTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $motherUser;
    protected Patient $mother;
    protected Patient $child;
    protected ChildRecord $childRecord;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-20 10:00:00');

        $this->adminUser = User::create(['name' => 'Midwife Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin']);
        $this->motherUser = User::create(['name' => 'Joy Bautista', 'email' => 'joy@example.com', 'password' => Hash::make('secret123'), 'role' => 'user']);

        $base = ['address' => 'Purok 4', 'barangay' => 'Bicao', 'emergency_contact_name' => 'Ben Bautista', 'emergency_contact_phone' => '09223334444', 'status' => 'Active'];
        $this->mother = Patient::create($base + ['user_id' => $this->motherUser->id, 'first_name' => 'Joy', 'last_name' => 'Bautista', 'dob' => '1997-02-02', 'gender' => 'Female', 'phone' => '09175550000', 'registration_type' => 'Maternal']);
        MaternalRecord::create(['patient_id' => $this->mother->id, 'lmp' => '2026-03-01', 'blood_type' => 'B+']);

        $this->child = Patient::create($base + ['first_name' => 'Nico', 'last_name' => 'Bautista', 'dob' => '2026-02-20', 'gender' => 'Male', 'phone' => '09175550000', 'registration_type' => 'Child']);
        $this->childRecord = ChildRecord::create(['patient_id' => $this->child->id, 'mother_id' => $this->mother->id]);
        Immunization::create(['child_record_id' => $this->childRecord->id, 'vaccine_name' => 'OPV', 'dose_number' => 2, 'scheduled_date' => '2026-05-01', 'status' => 'Scheduled']);
        Immunization::create(['child_record_id' => $this->childRecord->id, 'vaccine_name' => 'MMR', 'dose_number' => 1, 'scheduled_date' => '2026-11-20', 'status' => 'Scheduled']);
        GrowthMeasurement::create(['child_record_id' => $this->childRecord->id, 'date' => '2026-07-20', 'age_months' => 0, 'weight_kg' => 7.0, 'height_cm' => 65]);
    }

    public function test_dashboard_chart_and_activity_come_from_records(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/dashboard')->assertOk();

        $months = $response->viewData('chartMonths');
        $this->assertCount(6, $months);
        $this->assertSame('Aug 2026', $months[5]['label']);
        $this->assertSame(1, $months[5]['Maternal']);
        $this->assertSame(1, $months[5]['Child']);

        $response->assertSee('Nico Bautista')
            ->assertSee('Overdue')
            ->assertDontSee('Ethan Gomez')
            ->assertDontSee('Missed polio vaccine');
    }

    public function test_patient_portal_shows_this_family_only(): void
    {
        ChatMessage::create(['sender_id' => $this->adminUser->id, 'receiver_id' => $this->motherUser->id, 'message' => 'See you on Monday for the OPV dose.', 'is_read' => false]);

        $this->actingAs($this->motherUser)->get('/portal')
            ->assertOk()
            ->assertSee('Nico Bautista')
            ->assertSee('OPV — dose 2')
            ->assertSee('1 overdue')
            ->assertSee('See you on Monday for the OPV dose.')
            ->assertSee('B+')
            ->assertSee('#MC-2026-' . sprintf('%03d', $this->mother->id))
            ->assertDontSee('Liam')
            ->assertDontSee('Sofia')
            ->assertDontSee('OGTT results are normal')
            ->assertDontSee('#MC-2024-0089');
    }

    public function test_next_vaccine_on_portal_is_upcoming_not_overdue(): void
    {
        $this->actingAs($this->motherUser)->get('/portal')
            ->assertViewHas('nextVaccineDate', 'Nov 20')
            ->assertViewHas('overdueVaccinesCount', 1);
    }

    public function test_sms_page_uses_real_counts_status_and_no_other_patients_names(): void
    {
        SmsMessage::create(['patient_id' => $this->mother->id, 'phone_number' => '09175550000', 'message' => 'Hi', 'status' => 'Sent', 'sent_at' => now(), 'type' => 'Manual']);
        SmsMessage::create(['patient_id' => $this->mother->id, 'phone_number' => '09175550000', 'message' => 'Hi again', 'status' => 'Failed', 'type' => 'Manual']);

        $response = $this->actingAs($this->adminUser)->get('/sms?patient_id=' . $this->mother->id)->assertOk();

        $response->assertViewHas('stats', fn ($stats) => $stats['sent'] === 1 && $stats['failed'] === 1)
            ->assertSee('Failed')
            ->assertSee('value="' . $this->mother->id . '" data-first-name="Joy" selected', false)
            ->assertDontSee('Elena Dela Cruz')
            ->assertDontSee('Liam Gabriel')
            ->assertDontSee('1,248')
            ->assertDontSee('128 Mothers');
    }

    public function test_growth_page_reminders_and_milestones_are_for_this_child(): void
    {
        $this->actingAs($this->adminUser)->get('/growth?id=' . $this->child->id)
            ->assertOk()
            ->assertSee('1 vaccine dose overdue')
            ->assertSee('MMR dose 1')
            ->assertSee('Sitting without support')
            ->assertDontSee('Oct 12, 2023')
            ->assertDontSee('Walking independently')
            ->assertDontSee('Achieved at 14 months');
    }

    public function test_maternal_page_contacts_the_real_patient(): void
    {
        MaternalCheckup::create(['maternal_record_id' => $this->mother->maternalRecord->id, 'visit_number' => 1, 'date' => '2026-08-20', 'weight_kg' => 60, 'bp' => '110/70']);

        $this->actingAs($this->adminUser)->get('/maternal?id=' . $this->mother->id)
            ->assertOk()
            ->assertSee('tel:09175550000', false)
            ->assertSee('Ben Bautista')
            ->assertSee('Next prenatal visit')
            ->assertDontSee('0917-555-0123')
            ->assertDontSee('Second dose of Tetanus Toxoid');
    }

    public function test_immunization_page_has_no_sample_journal_or_names(): void
    {
        $this->actingAs($this->motherUser)->get('/immunization')
            ->assertOk()
            ->assertSee('Nico')
            ->assertDontSee('Keep Maria hydrated')
            ->assertDontSee('No symptoms reported after PCV13')
            ->assertDontSee('Today, 10:45 AM');
    }

    public function test_unread_badge_counts_real_messages(): void
    {
        $response = $this->actingAs($this->motherUser)->get('/portal');
        $response->assertDontSee('aria-label="4 unread"', false);

        ChatMessage::create(['sender_id' => $this->adminUser->id, 'receiver_id' => $this->motherUser->id, 'message' => 'Hello', 'is_read' => false]);
        ChatMessage::create(['sender_id' => $this->adminUser->id, 'receiver_id' => $this->motherUser->id, 'message' => 'Reminder', 'is_read' => false]);

        $this->actingAs($this->motherUser)->get('/portal')->assertSee('aria-label="2 unread"', false);
    }

    public function test_login_and_records_pages_make_no_false_claims(): void
    {
        $this->get('/')->assertOk()->assertDontSee('href="#"', false);

        $this->actingAs($this->adminUser)->get('/records')
            ->assertOk()
            ->assertDontSee('backup completed')
            ->assertDontSee('Generate CSV reports');

        $this->actingAs($this->adminUser)->get('/register')
            ->assertOk()
            ->assertDontSee('scan and auto-fill PhilHealth')
            ->assertDontSee('Save Draft');
    }
}
