<?php

namespace Tests\Feature;

use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use App\Services\PrenatalAssessment;
use App\Services\WhoGrowthStandards;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ClinicalAssessmentTest extends TestCase
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

    protected function mother(array $recordOverrides = [], array $patientOverrides = []): Patient
    {
        $patient = Patient::create(array_merge([
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'dob' => '1996-03-10',
            'gender' => 'Female',
            'phone' => '09171234567',
            'address' => 'Purok 1',
            'barangay' => 'Bicao',
            'emergency_contact_name' => 'Juan Santos',
            'emergency_contact_phone' => '09222222222',
            'registration_type' => 'Maternal',
            'status' => 'Active',
        ], $patientOverrides));

        MaternalRecord::create(array_merge([
            'patient_id' => $patient->id,
            'lmp' => '2026-01-01',
            'edd' => '2026-10-08',
            'gravida' => 1,
            'para' => 0,
        ], $recordOverrides));

        return $patient;
    }

    protected function child(string $dob, string $gender = 'Male'): Patient
    {
        $patient = Patient::create([
            'first_name' => 'Liam',
            'last_name' => 'Santos',
            'dob' => $dob,
            'gender' => $gender,
            'phone' => '09171234567',
            'address' => 'Purok 1',
            'barangay' => 'Bicao',
            'emergency_contact_name' => 'Maria Santos',
            'emergency_contact_phone' => '09222222222',
            'registration_type' => 'Child',
            'status' => 'Active',
        ]);
        ChildRecord::create(['patient_id' => $patient->id, 'birth_weight_kg' => 3.3, 'birth_height_cm' => 50]);

        return $patient;
    }

    protected function logVisit(Patient $patient, array $data)
    {
        return $this->actingAs($this->adminUser)->post('/maternal?id=' . $patient->id, $data);
    }

    // --- #7 Gestational age comes from the LMP -------------------------------------

    public function test_gestational_age_is_counted_from_lmp_not_visit_number(): void
    {
        $patient = $this->mother(); // LMP 2026-01-01 → 231 days on 2026-08-20

        $this->logVisit($patient, ['weight_kg' => 62, 'bp' => '110/70', 'fetal_heart_rate' => 140])
            ->assertSessionHas('success');

        $checkup = MaternalCheckup::first();
        $this->assertSame('33w 0d', $checkup->age_of_gestation);
        $this->assertSame(PrenatalAssessment::HEALTHY, $checkup->status);
        // 28–36 weeks: every 2 weeks
        $this->assertSame('2026-09-03', $checkup->next_visit_date->toDateString());
    }

    public function test_next_visit_interval_follows_gestational_age(): void
    {
        $assessment = new PrenatalAssessment();
        $visit = Carbon::parse('2026-08-20');

        $this->assertSame('2026-09-17', $assessment->nextVisitDate($visit, 20 * 7)->toDateString());
        $this->assertSame('2026-09-03', $assessment->nextVisitDate($visit, 30 * 7)->toDateString());
        $this->assertSame('2026-08-27', $assessment->nextVisitDate($visit, 37 * 7)->toDateString());
        $this->assertSame('2026-09-17', $assessment->nextVisitDate($visit, null)->toDateString());
    }

    public function test_unknown_lmp_leaves_gestational_age_blank_and_is_flagged(): void
    {
        $patient = $this->mother(['lmp' => null, 'edd' => null]);

        $this->logVisit($patient, ['weight_kg' => 62, 'bp' => '110/70']);

        $this->assertNull(MaternalCheckup::first()->age_of_gestation);

        $this->actingAs($this->adminUser)->get('/maternal?id=' . $patient->id)
            ->assertOk()
            ->assertSee('Gestational age unknown')
            ->assertSee('LMP not recorded');
    }

    public function test_post_term_pregnancy_is_flagged(): void
    {
        $patient = $this->mother(['lmp' => '2025-10-20']); // 304 days

        $this->logVisit($patient, ['weight_kg' => 70, 'bp' => '115/75', 'fetal_heart_rate' => 138]);

        $checkup = MaternalCheckup::first();
        $this->assertSame('43w 3d', $checkup->age_of_gestation);
        $this->assertSame(PrenatalAssessment::HIGH_RISK, $checkup->status);
        $this->assertStringContainsString('Post-term', $checkup->risk_flags[0]['message']);
    }

    // --- #8 Prenatal risk flags ------------------------------------------------------

    public function test_high_blood_pressure_after_20_weeks_flags_preeclampsia_and_escalates_patient(): void
    {
        $patient = $this->mother();

        $this->logVisit($patient, ['weight_kg' => 64, 'bp' => '150/95', 'fetal_heart_rate' => 142])
            ->assertSessionHas('warning');

        $checkup = MaternalCheckup::first();
        $this->assertSame(PrenatalAssessment::HIGH_RISK, $checkup->status);
        $this->assertStringContainsString('pre-eclampsia', $checkup->risk_flags[0]['message']);
        $this->assertSame('High Risk', $patient->fresh()->status);
    }

    public function test_severe_hypertension_requires_urgent_referral(): void
    {
        $patient = $this->mother();

        $this->logVisit($patient, ['weight_kg' => 64, 'bp' => '165/112', 'fetal_heart_rate' => 142]);

        $this->assertStringContainsString('refer urgently', MaternalCheckup::first()->risk_flags[0]['message']);
    }

    public function test_abnormal_fetal_heart_rate_is_high_risk(): void
    {
        $patient = $this->mother();

        $this->logVisit($patient, ['weight_kg' => 64, 'bp' => '110/70', 'fetal_heart_rate' => 100]);

        $checkup = MaternalCheckup::first();
        $this->assertSame(PrenatalAssessment::HIGH_RISK, $checkup->status);
        $this->assertStringContainsString('below 110 bpm', $checkup->risk_flags[0]['message']);
    }

    public function test_low_blood_pressure_is_monitor_not_high_risk(): void
    {
        $patient = $this->mother();

        $this->logVisit($patient, ['weight_kg' => 55, 'bp' => '85/55', 'fetal_heart_rate' => 140]);

        $this->assertSame(PrenatalAssessment::MONITOR, MaternalCheckup::first()->status);
        $this->assertSame('Active', $patient->fresh()->status);
    }

    public function test_fetal_heart_rate_is_optional_but_blood_pressure_must_be_valid(): void
    {
        $patient = $this->mother();

        $this->logVisit($patient, ['weight_kg' => 55, 'bp' => 'normal'])->assertSessionHasErrors('bp');
        $this->assertDatabaseCount('maternal_checkups', 0);

        $this->logVisit($patient, ['weight_kg' => 55, 'bp' => '110 / 70'])->assertSessionHasNoErrors();
        $this->assertSame('110/70', MaternalCheckup::first()->bp);
        $this->assertNull(MaternalCheckup::first()->fetal_heart_rate);
    }

    public function test_risk_profile_lists_patient_risk_factors(): void
    {
        $patient = $this->mother(
            ['medical_history' => ['Hypertension' => '1', 'Asthma' => '0']],
            ['dob' => '2010-05-01'] // 16 years old
        );

        $this->actingAs($this->adminUser)->get('/maternal?id=' . $patient->id)
            ->assertOk()
            ->assertSee('Maternal age under 18 (16 years).')
            ->assertSee('History of Hypertension.')
            ->assertDontSee('History of Asthma.')
            ->assertDontSee('Preeclampsia Risk');
    }

    public function test_maternal_page_shows_recorded_vitals_not_placeholders(): void
    {
        $patient = $this->mother();
        $this->logVisit($patient, ['weight_kg' => 64, 'bp' => '150/95', 'fetal_heart_rate' => 142]);

        $this->actingAs($this->adminUser)->get('/maternal?id=' . $patient->id)
            ->assertSee('150/95')
            ->assertSee('Week 33 (Current)')
            ->assertDontSee('Mar 12, 2024');
    }

    public function test_synced_checkup_is_validated_and_assessed(): void
    {
        $patient = $this->mother();
        $recordId = $patient->maternalRecord->id;

        $this->actingAs($this->adminUser)->postJson('/api/sync/batch', ['items' => [
            ['id' => 1, 'type' => 'maternal_checkup', 'data' => ['maternal_record_id' => $recordId, 'weight_kg' => 60, 'bp' => 'high']],
            ['id' => 2, 'type' => 'maternal_checkup', 'data' => ['maternal_record_id' => $recordId, 'weight_kg' => 60, 'bp' => '145/92', 'date' => '2026-08-13']],
        ]])->assertJson(['synced_ids' => [2]]);

        $checkup = MaternalCheckup::sole();
        $this->assertSame('32w 0d', $checkup->age_of_gestation);
        $this->assertSame(PrenatalAssessment::HIGH_RISK, $checkup->status);
    }

    // --- #8 WHO growth assessment ----------------------------------------------------

    public function test_growth_measurement_gets_who_z_scores_and_status(): void
    {
        $child = $this->child('2025-08-20'); // 12 months old

        $this->actingAs($this->adminUser)->post('/growth?id=' . $child->id, ['weight_kg' => 7.7, 'height_cm' => 75.7])
            ->assertSessionHas('warning');

        $measurement = GrowthMeasurement::latest('id')->first();
        $this->assertSame(12, $measurement->age_months);
        $this->assertEqualsWithDelta(-2.05, $measurement->weight_for_age_z, 0.01);
        $this->assertEqualsWithDelta(0.0, $measurement->height_for_age_z, 0.05);
        $this->assertSame('Wasted', $measurement->status);
    }

    public function test_healthy_child_is_normal(): void
    {
        $child = $this->child('2025-08-20');

        $this->actingAs($this->adminUser)->post('/growth?id=' . $child->id, ['weight_kg' => 9.6, 'height_cm' => 75.7])
            ->assertSessionHas('success');

        $this->assertSame(WhoGrowthStandards::NORMAL, GrowthMeasurement::latest('id')->first()->status);
    }

    public function test_implausible_measurement_asks_for_recheck(): void
    {
        $child = $this->child('2026-02-20'); // 6 months

        $this->actingAs($this->adminUser)->post('/growth?id=' . $child->id, ['weight_kg' => 76, 'height_cm' => 67]);

        $this->assertSame(WhoGrowthStandards::RECHECK, GrowthMeasurement::latest('id')->first()->status);
    }

    public function test_children_over_five_are_not_assessed(): void
    {
        $child = $this->child('2020-01-01');

        $this->actingAs($this->adminUser)->post('/growth?id=' . $child->id, ['weight_kg' => 20, 'height_cm' => 115])
            ->assertSessionHas('success');

        $measurement = GrowthMeasurement::latest('id')->first();
        $this->assertSame(WhoGrowthStandards::NOT_ASSESSED, $measurement->status);
        $this->assertNull($measurement->weight_for_age_z);
    }

    public function test_low_birth_weight_registration_is_not_marked_normal(): void
    {
        $this->actingAs($this->adminUser)->post('/register', [
            'first_name' => 'Nene',
            'last_name' => 'Cruz',
            'dob' => '2026-08-18',
            'gender' => 'Female',
            'phone' => '09123456789',
            'address' => 'Purok 3',
            'emergency_contact_name' => 'Ana Cruz',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Child',
            'birth_weight_kg' => 1.9,
            'birth_height_cm' => 44.5,
        ])->assertRedirect(route('records'));

        $birth = GrowthMeasurement::sole();
        $this->assertLessThan(-2, $birth->weight_for_age_z);
        $this->assertNotSame(WhoGrowthStandards::NORMAL, $birth->status);
    }

    public function test_growth_page_shows_real_assessment(): void
    {
        $child = $this->child('2025-08-20');
        $this->actingAs($this->adminUser)->post('/growth?id=' . $child->id, ['weight_kg' => 7.7, 'height_cm' => 75.7]);

        $this->actingAs($this->adminUser)->get('/growth?id=' . $child->id)
            ->assertOk()
            ->assertSee('Wasted')
            ->assertSee('Weight-for-length')
            ->assertDontSee('WELL-NOURISHED')
            ->assertDontSee('Percentile 65%');
    }

    public function test_recalculate_command_reassesses_existing_rows(): void
    {
        $child = $this->child('2025-08-20');
        $patient = $this->mother();

        // Rows written before this feature existed: hardcoded status, no z-scores, visit-number AOG.
        DB::table('growth_measurements')->insert([
            'child_record_id' => $child->childRecord->id, 'date' => '2026-08-20', 'age_months' => 12,
            'weight_kg' => 7.7, 'height_cm' => 75.7, 'status' => 'Normal', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('maternal_checkups')->insert([
            'maternal_record_id' => $patient->maternalRecord->id, 'visit_number' => 1, 'date' => '2026-08-20',
            'weight_kg' => 64, 'bp' => '150/95', 'age_of_gestation' => '4w 0d', 'status' => 'Healthy',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('assessments:recalculate')->assertSuccessful();

        $this->assertSame('Wasted', GrowthMeasurement::sole()->status);
        $checkup = MaternalCheckup::sole();
        $this->assertSame('33w 0d', $checkup->age_of_gestation);
        $this->assertSame(PrenatalAssessment::HIGH_RISK, $checkup->status);
    }
}
