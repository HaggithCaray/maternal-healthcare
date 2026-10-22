<?php

namespace Tests\Feature;

use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\PostpartumVisit;
use App\Models\User;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    private string $settingsPath;

    /** Whatever gateway configuration was on this machine before the test, put back after. */
    private ?string $originalSettings = null;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-20 10:00:00');

        $this->settingsPath = storage_path('app/sms_settings.json');
        $this->originalSettings = file_exists($this->settingsPath) ? file_get_contents($this->settingsPath) : null;
    }

    protected function tearDown(): void
    {
        $this->originalSettings === null
            ? @unlink($this->settingsPath)
            : file_put_contents($this->settingsPath, $this->originalSettings);

        parent::tearDown();
    }

    /** Store gateway settings exactly as the SMS page would, reminders on or off. */
    private function setReminders(bool $enabled): void
    {
        app(SmsService::class)->saveSettings('https://api.sms-gate.app/3rdparty/v1', 'gw-user', 'gw-pass', $enabled);
    }

    /**
     * A mother with a phone, her maternal record, and a linked newborn.
     *
     * @return array{0: Patient, 1: MaternalRecord, 2: Patient, 3: ChildRecord}
     */
    private function family(): array
    {
        $mother = Patient::create([
            'first_name' => 'Elena',
            'last_name' => 'Cruz',
            'dob' => '1995-01-01',
            'gender' => 'Female',
            'phone' => '09171234567',
            'address' => 'Purok 1',
            'barangay' => 'Bicao',
            'emergency_contact_name' => 'Juan Cruz',
            'emergency_contact_phone' => '09222222222',
            'registration_type' => 'Maternal',
            'status' => 'Active',
        ]);

        $record = MaternalRecord::create([
            'patient_id' => $mother->id,
            'lmp' => '2026-01-01',
            'edd' => '2026-10-08',
            'gravida' => 2,
            'para' => 1,
        ]);

        $child = Patient::create([
            'first_name' => 'Baby',
            'last_name' => 'Cruz',
            'dob' => '2026-08-10',
            'gender' => 'Female',
            'registration_type' => 'Child',
            'status' => 'Active',
        ]);

        $childRecord = ChildRecord::create([
            'patient_id' => $child->id,
            'mother_id' => $mother->id,
            'birth_weight_kg' => 3.2,
            'birth_height_cm' => 50,
        ]);

        return [$mother, $record, $child, $childRecord];
    }

    private function doseFor(ChildRecord $childRecord, Carbon $scheduled): Immunization
    {
        return Immunization::create([
            'child_record_id' => $childRecord->id,
            'vaccine_name' => 'Pentavalent (DPT-HepB-Hib)',
            'dose_number' => 1,
            'scheduled_date' => $scheduled->toDateString(),
            'status' => 'Scheduled',
        ]);
    }

    // --- the off switch --------------------------------------------------------------

    public function test_nothing_is_sent_until_the_admin_switches_reminders_on(): void
    {
        Http::fake();
        $this->setReminders(false);

        [, , , $childRecord] = $this->family();
        $this->doseFor($childRecord, Carbon::tomorrow());

        $this->artisan('reminders:send')
            ->expectsOutputToContain('turned off')
            ->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_messages', 0);
    }

    public function test_the_reminders_switch_is_saved_from_the_sms_page(): void
    {
        $admin = User::create([
            'name' => 'Admin Midwife',
            'email' => 'admin@maternal.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->post('/sms/settings', [
            'url' => 'https://api.sms-gate.app/3rdparty/v1',
            'username' => 'gw-user',
            'password' => 'gw-pass',
            'reminders_enabled' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertTrue(app(SmsService::class)->remindersEnabled());

        // Unticking the box and saving turns them off again.
        $this->actingAs($admin)->post('/sms/settings', [
            'url' => 'https://api.sms-gate.app/3rdparty/v1',
            'username' => 'gw-user',
            'password' => 'gw-pass',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertFalse(app(SmsService::class)->remindersEnabled());
    }

    // --- vaccine reminders -----------------------------------------------------------

    public function test_a_dose_due_within_two_days_is_texted_to_the_mother(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [, , $child, $childRecord] = $this->family();
        $dose = $this->doseFor($childRecord, Carbon::tomorrow());

        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/3rdparty/v1/messages')
                && $request['phoneNumbers'] === ['+639171234567']
                && str_contains($request['textMessage']['text'], 'Pentavalent');
        });

        $this->assertDatabaseHas('sms_messages', [
            'patient_id' => $child->id,
            'phone_number' => '09171234567',
            'status' => 'Sent',
            'type' => 'Reminder',
            'dedupe_key' => "vaccine:{$dose->id}",
        ]);
    }

    public function test_a_sent_reminder_is_never_sent_again(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [, , , $childRecord] = $this->family();
        $this->doseFor($childRecord, Carbon::tomorrow());

        $this->artisan('reminders:send')->assertExitCode(0);
        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('sms_messages', 1);
    }

    public function test_a_failed_send_is_retried_on_the_next_run_without_duplicating(): void
    {
        $this->setReminders(true);

        [, , , $childRecord] = $this->family();
        $dose = $this->doseFor($childRecord, Carbon::tomorrow());

        // First run: the gateway refuses. Second run: it goes through.
        $attempt = 0;
        Http::fake(function () use (&$attempt) {
            $attempt++;

            return $attempt === 1
                ? Http::response(['message' => 'gateway busy'], 500)
                : Http::response([], 202);
        });

        $this->artisan('reminders:send')->assertExitCode(0);
        $this->assertDatabaseHas('sms_messages', ['dedupe_key' => "vaccine:{$dose->id}", 'status' => 'Failed']);

        $this->artisan('reminders:send')->assertExitCode(0);

        // Same row, now Sent — one message record, not two.
        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertDatabaseHas('sms_messages', ['dedupe_key' => "vaccine:{$dose->id}", 'status' => 'Sent']);
    }

    // --- prenatal and postpartum reminders --------------------------------------------

    public function test_prenatal_reminder_follows_only_the_latest_checkup(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [$mother, $record] = $this->family();

        // An older checkup whose visit came and went, then a newer one due tomorrow:
        // only the newest next-visit date still counts.
        MaternalCheckup::create([
            'maternal_record_id' => $record->id,
            'visit_number' => 1,
            'date' => Carbon::today()->subDays(45)->toDateString(),
            'weight_kg' => 63,
            'bp' => '118/76',
            'next_visit_date' => Carbon::today()->subDays(17)->toDateString(),
        ]);

        $latest = MaternalCheckup::create([
            'maternal_record_id' => $record->id,
            'visit_number' => 2,
            'date' => Carbon::today()->subDays(3)->toDateString(),
            'weight_kg' => 64,
            'bp' => '120/80',
            'next_visit_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $this->artisan('reminders:send')->assertExitCode(0);

        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertDatabaseHas('sms_messages', [
            'patient_id' => $mother->id,
            'dedupe_key' => "nextvisit:{$latest->id}",
            'status' => 'Sent',
        ]);
        $this->assertStringContainsString('tomorrow', \App\Models\SmsMessage::first()->message);
    }

    public function test_no_prenatal_reminder_when_the_next_visit_is_still_ahead(): void
    {
        Http::fake();
        $this->setReminders(true);

        [, $record] = $this->family();

        // Latest checkup points three days out, even though an older one is long overdue.
        MaternalCheckup::create([
            'maternal_record_id' => $record->id,
            'visit_number' => 1,
            'date' => Carbon::today()->subDays(60)->toDateString(),
            'weight_kg' => 63,
            'bp' => '118/76',
            'next_visit_date' => Carbon::today()->subDays(30)->toDateString(),
        ]);

        MaternalCheckup::create([
            'maternal_record_id' => $record->id,
            'visit_number' => 2,
            'date' => Carbon::today()->toDateString(),
            'weight_kg' => 64,
            'bp' => '120/80',
            'next_visit_date' => Carbon::today()->addDays(3)->toDateString(),
        ]);

        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_messages', 0);
    }

    public function test_an_overdue_postpartum_visit_is_reminded_once(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [$mother, $record] = $this->family();
        $record->update(['delivery_date' => Carbon::today()->subDays(10)->toDateString()]);

        $visit = PostpartumVisit::create([
            'maternal_record_id' => $record->id,
            'date' => Carbon::today()->toDateString(),
            'weight_kg' => 64,
            'bp' => '118/76',
            'attendant' => 'Midwife',
            'next_visit_date' => Carbon::yesterday()->toDateString(),
        ]);

        $this->artisan('reminders:send')->assertExitCode(0);
        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertSentCount(1);
        $this->assertDatabaseCount('sms_messages', 1);
        $this->assertDatabaseHas('sms_messages', [
            'patient_id' => $mother->id,
            'dedupe_key' => "postpartum:{$visit->id}",
            'status' => 'Sent',
        ]);

        $row = \App\Models\SmsMessage::first();
        $this->assertStringContainsString('postpartum checkup was due', $row->message);
    }

    // --- who is never texted ---------------------------------------------------------

    public function test_archived_patients_are_never_texted(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [$mother, $record, $child, $childRecord] = $this->family();
        $this->doseFor($childRecord, Carbon::tomorrow());
        MaternalCheckup::create([
            'maternal_record_id' => $record->id, 'visit_number' => 1, 'date' => Carbon::today()->subDays(28)->toDateString(),
            'weight_kg' => 63, 'bp' => '118/76', 'next_visit_date' => Carbon::tomorrow()->toDateString(),
        ]);
        $mother->update(['status' => 'Archived']);
        $child->update(['status' => 'Archived']);

        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_messages', 0);
    }

    public function test_an_archived_childs_mother_is_not_texted_about_its_vaccines(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [, , $child, $childRecord] = $this->family();
        $this->doseFor($childRecord, Carbon::tomorrow());
        $child->update(['status' => 'Archived']);

        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_a_mother_who_has_delivered_gets_no_prenatal_reminder(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [, $record] = $this->family();
        MaternalCheckup::create([
            'maternal_record_id' => $record->id, 'visit_number' => 1, 'date' => Carbon::today()->subDays(40)->toDateString(),
            'weight_kg' => 63, 'bp' => '118/76', 'next_visit_date' => Carbon::today()->subDays(5)->toDateString(),
        ]);
        $record->update(['delivery_date' => Carbon::today()->subDays(3)->toDateString()]);

        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_a_visit_missed_long_ago_is_not_texted(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [, $record] = $this->family();
        MaternalCheckup::create([
            'maternal_record_id' => $record->id, 'visit_number' => 1, 'date' => Carbon::today()->subDays(80)->toDateString(),
            'weight_kg' => 63, 'bp' => '118/76',
            'next_visit_date' => Carbon::today()->subDays(\App\Services\ReminderService::OVERDUE_WINDOW_DAYS + 1)->toDateString(),
        ]);

        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_a_recently_missed_visit_is_still_texted(): void
    {
        Http::fake(['*' => Http::response([], 202)]);
        $this->setReminders(true);

        [, $record] = $this->family();
        MaternalCheckup::create([
            'maternal_record_id' => $record->id, 'visit_number' => 1, 'date' => Carbon::today()->subDays(40)->toDateString(),
            'weight_kg' => 63, 'bp' => '118/76',
            'next_visit_date' => Carbon::today()->subDays(\App\Services\ReminderService::OVERDUE_WINDOW_DAYS)->toDateString(),
        ]);

        $this->artisan('reminders:send')->assertExitCode(0);

        Http::assertSentCount(1);
        $this->assertStringContainsString('was due on', \App\Models\SmsMessage::sole()->message);
    }
}
