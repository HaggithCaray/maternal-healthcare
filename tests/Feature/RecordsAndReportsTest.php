<?php

namespace Tests\Feature;

use App\Models\ChildRecord;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RecordsAndReportsTest extends TestCase
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

    protected function patient(array $overrides = [], ?string $createdAt = null): Patient
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
        ], $overrides));

        if ($createdAt) {
            $patient->created_at = Carbon::parse($createdAt);
            $patient->save();
        }

        return $patient;
    }

    protected function maternalForm(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'dob' => '1998-04-12',
            'gender' => 'Female',
            'phone' => '09123456789',
            'address' => 'Purok 2, Bicao',
            'emergency_contact_name' => 'Pedro Reyes',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Maternal',
            'lmp' => '2026-03-01',
        ], $overrides);
    }

    // --- #9 Medical history is stored in one format ----------------------------------

    public function test_registration_stores_medical_history_as_list_and_saves_allergies(): void
    {
        $this->actingAs($this->adminUser)->post('/register', $this->maternalForm([
            'medical_history' => ['Hypertension' => '1', 'Anemia' => '1'],
            'allergies' => 'Penicillin',
        ]))->assertRedirect(route('records'));

        $record = MaternalRecord::sole();
        $this->assertSame(['Hypertension', 'Anemia'], $record->medical_history);
        $this->assertSame('Penicillin', $record->allergies);
    }

    public function test_unknown_conditions_are_ignored(): void
    {
        $this->assertSame(
            ['Diabetes'],
            MaternalRecord::normalizeConditions(['Diabetes' => '1', 'Hacked' => '1', 'Asthma' => '0'])
        );
        $this->assertSame(['Asthma', 'Anemia'], MaternalRecord::normalizeConditions(['Anemia', 'Asthma', 'Nope']));
        $this->assertSame([], MaternalRecord::normalizeConditions('Hypertension'));
    }

    public function test_edit_form_checks_conditions_saved_in_the_old_format(): void
    {
        $patient = $this->patient();
        MaternalRecord::create(['patient_id' => $patient->id, 'medical_history' => ['Hypertension' => '1', 'Asthma' => '0']]);

        $response = $this->actingAs($this->adminUser)->get('/patients/' . $patient->id . '/edit')->assertOk();

        $response->assertSee('name="medical_history[Hypertension]" value="1" checked', false);
        $response->assertDontSee('name="medical_history[Asthma]" value="1" checked', false);
    }

    public function test_offline_sync_stores_medical_history_as_list(): void
    {
        $this->actingAs($this->adminUser)->postJson('/api/sync/batch', ['items' => [[
            'id' => 1,
            'type' => 'patient_registration',
            'data' => $this->maternalForm(['medical_history' => ['Diabetes' => '1', 'Asthma' => '0']]),
        ]]])->assertJson(['success' => true]);

        $this->assertSame(['Diabetes'], MaternalRecord::sole()->medical_history);
    }

    public function test_migration_converts_old_medical_history_rows(): void
    {
        $patient = $this->patient();
        $record = MaternalRecord::create(['patient_id' => $patient->id]);
        DB::table('maternal_records')->where('id', $record->id)->update([
            'medical_history' => json_encode(['Hypertension' => true, 'Diabetes' => false, 'Anemia' => '1']),
        ]);

        (require database_path('migrations/2026_09_27_000001_normalize_maternal_medical_history.php'))->up();

        $this->assertSame(['Hypertension', 'Anemia'], $record->fresh()->medical_history);
    }

    // --- #10 Reports use real data, one year at a time --------------------------------

    public function test_monthly_registrations_do_not_merge_years(): void
    {
        $this->patient([], '2025-03-10');
        $this->patient(['registration_type' => 'Child'], '2026-03-15');
        $this->patient([], '2026-03-20');

        $response = $this->actingAs($this->adminUser)->get('/reports?year=2026')->assertOk();
        $this->assertSame(['Maternal' => 1, 'Child' => 1], $response->viewData('monthlyRegistrations')[3]);
        $this->assertSame(2, $response->viewData('registeredInYear'));
        $this->assertSame([2026, 2025], $response->viewData('years'));

        $response = $this->actingAs($this->adminUser)->get('/reports?year=2025')->assertOk();
        $this->assertSame(['Maternal' => 1, 'Child' => 0], $response->viewData('monthlyRegistrations')[3]);
    }

    public function test_unknown_year_falls_back_to_current_year(): void
    {
        $this->actingAs($this->adminUser)->get('/reports?year=1999')->assertViewHas('year', 2026);
    }

    public function test_immunization_coverage_only_counts_doses_already_due(): void
    {
        $child = $this->patient(['registration_type' => 'Child', 'dob' => '2026-01-01']);
        $record = ChildRecord::create(['patient_id' => $child->id]);

        foreach ([['2026-02-12', 'Given'], ['2026-03-12', 'Scheduled'], ['2026-10-01', 'Scheduled']] as [$date, $status]) {
            Immunization::create([
                'child_record_id' => $record->id,
                'vaccine_name' => 'OPV',
                'dose_number' => 1,
                'scheduled_date' => $date,
                'status' => $status,
            ]);
        }

        $this->actingAs($this->adminUser)->get('/reports?year=2026')
            ->assertViewHas('dosesDue', 2)
            ->assertViewHas('dosesGiven', 1)
            ->assertViewHas('complianceRate', 50)
            ->assertViewHas('overdueDoses', 1);
    }

    public function test_nutrition_summary_uses_each_childs_latest_measurement(): void
    {
        $child = $this->patient(['registration_type' => 'Child', 'dob' => '2025-08-20', 'gender' => 'Male']);
        $record = ChildRecord::create(['patient_id' => $child->id]);
        GrowthMeasurement::create(['child_record_id' => $record->id, 'date' => '2026-02-20', 'age_months' => 0, 'weight_kg' => 5.0, 'height_cm' => 66]);
        GrowthMeasurement::create(['child_record_id' => $record->id, 'date' => '2026-08-20', 'age_months' => 0, 'weight_kg' => 9.6, 'height_cm' => 75.7]);

        $response = $this->actingAs($this->adminUser)->get('/reports')->assertOk();

        $this->assertSame(1, $response->viewData('childrenMeasured'));
        $this->assertSame(['Normal' => 1], $response->viewData('nutritionSummary'));
    }

    public function test_reports_page_has_no_placeholder_data(): void
    {
        $this->patient();

        $this->actingAs($this->adminUser)->get('/reports')
            ->assertOk()
            ->assertSee('Registrations per month, 2026')
            ->assertDontSee('Oct 31, 2023')
            ->assertDontSee('Reports Generated (Oct)')
            ->assertDontSee('Storage Used');
    }
}
