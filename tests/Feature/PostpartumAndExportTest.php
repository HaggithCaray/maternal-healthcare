<?php

namespace Tests\Feature;

use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\PostpartumVisit;
use App\Models\User;
use App\Services\PrenatalAssessment;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PostpartumAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

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
    }

    /** A maternal patient with a record; $delivery optionally records the delivery date. */
    protected function mother(array $patientOverrides = [], ?string $delivery = null): Patient
    {
        $patient = Patient::create(array_merge([
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
        ], $patientOverrides));

        MaternalRecord::create([
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01',
            'edd' => '2026-10-08',
            'gravida' => 2,
            'para' => 1,
            'delivery_date' => $delivery,
        ]);

        return $patient->fresh();
    }

    // --- Feature 3: postpartum care tracking -----------------------------------------

    public function test_postpartum_visit_derives_days_status_and_next_visit(): void
    {
        // Delivered 10 days ago; a visit today is 10 days postpartum.
        $patient = $this->mother([], Carbon::today()->subDays(10)->toDateString());
        $record = $patient->maternalRecord;

        $visit = PostpartumVisit::create([
            'maternal_record_id' => $record->id,
            'date' => Carbon::today()->toDateString(),
            'weight_kg' => 64.5,
            'bp' => '118/76',
            'attendant' => 'Midwife',
        ]);

        $this->assertSame(10, $visit->days_postpartum);
        $this->assertSame(PrenatalAssessment::HEALTHY, $visit->status);
        // Next scheduled contact after day 10 is the 2-week (day 14) visit.
        $this->assertTrue($visit->next_visit_date->equalTo(Carbon::parse($record->delivery_date)->addDays(14)));
    }

    public function test_postpartum_high_bp_is_flagged_high_risk_and_escalates_patient(): void
    {
        $patient = $this->mother([], Carbon::today()->subDays(3)->toDateString());
        $record = $patient->maternalRecord;

        $visit = PostpartumVisit::create([
            'maternal_record_id' => $record->id,
            'date' => Carbon::today()->toDateString(),
            'weight_kg' => 66,
            'bp' => '165/112',
            'attendant' => 'Midwife',
        ]);

        $this->assertSame(PrenatalAssessment::HIGH_RISK, $visit->status);
        $this->assertNotEmpty($visit->risk_flags);
        $this->assertSame('High Risk', $patient->fresh()->status);
    }

    public function test_admin_can_log_postpartum_visit_from_maternal_page(): void
    {
        $patient = $this->mother([], Carbon::today()->subDays(5)->toDateString());

        $this->actingAs($this->adminUser)->post(route('maternal', ['id' => $patient->id]), [
            'visit_type' => 'postpartum',
            'weight_kg' => 63.2,
            'bp' => '120/80',
            'notes' => 'Doing well, breastfeeding.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('postpartum_visits', [
            'maternal_record_id' => $patient->maternalRecord->id,
            'weight_kg' => 63.2,
            'bp' => '120/80',
        ]);
    }

    public function test_postpartum_visit_is_refused_when_no_delivery_date(): void
    {
        $patient = $this->mother([], null);

        $this->actingAs($this->adminUser)->post(route('maternal', ['id' => $patient->id]), [
            'visit_type' => 'postpartum',
            'weight_kg' => 63.2,
            'bp' => '120/80',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('postpartum_visits', 0);
    }

    public function test_postpartum_form_rejects_bad_blood_pressure(): void
    {
        $patient = $this->mother([], Carbon::today()->subDays(5)->toDateString());

        $this->actingAs($this->adminUser)->post(route('maternal', ['id' => $patient->id]), [
            'visit_type' => 'postpartum',
            'weight_kg' => 63.2,
            'bp' => 'not-a-reading',
        ])->assertSessionHasErrors('bp');

        $this->assertDatabaseCount('postpartum_visits', 0);
    }

    public function test_offline_sync_accepts_postpartum_visit(): void
    {
        $patient = $this->mother([], Carbon::today()->subDays(4)->toDateString());

        $this->actingAs($this->adminUser)->postJson('/api/sync/batch', [
            'items' => [
                [
                    'id' => 'pp-1',
                    'type' => 'postpartum_visit',
                    'data' => [
                        'maternal_record_id' => $patient->maternalRecord->id,
                        'weight_kg' => 62.1,
                        'bp' => '122/82',
                    ],
                ],
            ],
        ])->assertOk()->assertJsonPath('synced', 1);

        $this->assertDatabaseHas('postpartum_visits', [
            'maternal_record_id' => $patient->maternalRecord->id,
            'weight_kg' => 62.1,
        ]);
    }

    public function test_child_registration_records_mothers_delivery_date(): void
    {
        $mother = $this->mother([], null);

        $this->actingAs($this->adminUser)->post('/register', [
            'first_name' => 'Baby',
            'last_name' => 'Cruz',
            'dob' => Carbon::today()->subDays(2)->toDateString(),
            'gender' => 'Female',
            'registration_type' => 'Child',
            'mother_id' => $mother->id,
        ])->assertSessionHas('success');

        $deliveryDate = $mother->fresh()->maternalRecord->delivery_date;

        $this->assertNotNull($deliveryDate);
        // Filled from the child's date of birth.
        $this->assertSame(Carbon::today()->subDays(2)->toDateString(), $deliveryDate->toDateString());
    }

    public function test_patient_sees_readonly_postpartum_section(): void
    {
        $motherUser = User::create([
            'name' => 'Elena Cruz',
            'email' => 'elena@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);

        $patient = $this->mother(['user_id' => $motherUser->id], Carbon::today()->subDays(3)->toDateString());

        PostpartumVisit::create([
            'maternal_record_id' => $patient->maternalRecord->id,
            'date' => Carbon::today()->toDateString(),
            'weight_kg' => 64,
            'bp' => '118/74',
            'attendant' => 'Midwife',
        ]);

        $this->actingAs($motherUser)->get(route('maternal'))
            ->assertOk()
            ->assertSee('Postpartum Care')
            ->assertSee('Day 3')
            // No admin log button for a patient.
            ->assertDontSee('Log Postpartum Visit', false);
    }

    // --- Feature 1: CSV export -------------------------------------------------------

    public function test_patient_export_respects_filters_and_is_admin_only(): void
    {
        $this->mother();
        $this->actingAs($this->adminUser)->get(route('export.patients'))
            ->assertOk()
            ->assertDownload('patients-' . Carbon::today()->toDateString() . '.csv');

        // A patient (role user) cannot export the masterlist.
        $patientUser = User::create([
            'name' => 'Mother',
            'email' => 'mother@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);
        $this->actingAs($patientUser)->get(route('export.patients'))->assertForbidden();
    }

    public function test_patient_export_only_includes_matching_rows(): void
    {
        $this->mother(['first_name' => 'Elena', 'last_name' => 'Cruz']);
        Patient::create([
            'first_name' => 'Juan',
            'last_name' => 'Santos',
            'dob' => '2020-01-01',
            'gender' => 'Male',
            'phone' => '09998887777',
            'address' => 'Purok 3',
            'barangay' => 'Bicao',
            'emergency_contact_name' => 'Maria Santos',
            'emergency_contact_phone' => '09111111111',
            'registration_type' => 'Child',
            'status' => 'Active',
        ]);

        // Filter to Maternal only: Cruz (Maternal) is present, Santos (Child) is not.
        $csv = $this->actingAs($this->adminUser)
            ->get(route('export.patients', ['type' => 'Maternal']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Cruz', $csv);
        $this->assertStringNotContainsString('Santos', $csv);
    }

    public function test_export_escapes_formula_injection_and_writes_audit(): void
    {
        // A name that a spreadsheet would treat as a formula.
        $this->mother(['first_name' => '=cmd|calc', 'last_name' => 'Danger']);

        $csv = $this->actingAs($this->adminUser)
            ->get(route('export.patients'))
            ->assertOk()
            ->streamedContent();

        // Neutralised with a leading apostrophe so Excel will not execute it.
        $this->assertStringContainsString("'=cmd|calc", $csv);

        $this->assertDatabaseHas('audit_logs', ['action' => 'export_patients']);
    }

    public function test_report_summary_export_contains_key_metrics(): void
    {
        $this->mother([], Carbon::today()->subDays(3)->toDateString());

        $csv = $this->actingAs($this->adminUser)
            ->get(route('export.summary', ['year' => Carbon::now()->year]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Registered patients (total)', $csv);
        $this->assertStringContainsString('Report year', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'export_report_summary']);
    }

    public function test_clinical_log_exports_are_admin_only(): void
    {
        $patientUser = User::create([
            'name' => 'Mother',
            'email' => 'mother2@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);

        foreach (['export.prenatal', 'export.postpartum', 'export.immunizations', 'export.summary'] as $route) {
            $this->actingAs($patientUser)->get(route($route))->assertForbidden();
        }
    }

    public function test_postpartum_export_lists_logged_visits(): void
    {
        $patient = $this->mother([], Carbon::today()->subDays(3)->toDateString());

        PostpartumVisit::create([
            'maternal_record_id' => $patient->maternalRecord->id,
            'date' => Carbon::today()->toDateString(),
            'weight_kg' => 64.1,
            'bp' => '118/74',
            'attendant' => 'Midwife',
        ]);

        $csv = $this->actingAs($this->adminUser)
            ->get(route('export.postpartum'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Elena Cruz', $csv);
        $this->assertStringContainsString('Day Postpartum', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'export_postpartum_visits']);
    }
}
