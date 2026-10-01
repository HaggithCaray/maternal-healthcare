<?php

namespace Tests\Feature;

use App\Models\ChildRecord;
use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhilippineTimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 23:30 UTC on Aug 19 is 07:30 on Aug 20 at the health station.
        Carbon::setTestNow(Carbon::parse('2026-08-19 23:30:00', 'UTC'));

        $this->adminUser = User::create([
            'name' => 'Midwife Rosa',
            'email' => 'rosa@health.test',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);
    }

    protected function patient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'dob' => '1998-04-12',
            'gender' => 'Female',
            'phone' => '09123456789',
            'address' => 'Purok 2',
            'emergency_contact_name' => 'Pedro Reyes',
            'emergency_contact_phone' => '09987654321',
            'registration_type' => 'Maternal',
            'barangay' => 'Bicao',
            'status' => 'Active',
        ], $overrides));
    }

    protected function runTimezoneMigration(string $direction): void
    {
        $migration = require database_path('migrations/2026_10_01_000000_store_timestamps_in_philippine_time.php');
        $migration->{$direction}();
    }

    public function test_the_app_runs_on_philippine_time(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('2026-08-20', Carbon::today()->toDateString());
    }

    public function test_an_early_morning_visit_is_dated_that_day(): void
    {
        $patient = $this->patient();
        MaternalRecord::create(['patient_id' => $patient->id, 'lmp' => '2026-03-01']);

        $this->actingAs($this->adminUser)
            ->post('/maternal?id=' . $patient->id, ['weight_kg' => 60, 'bp' => '110/70'])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-08-20', MaternalCheckup::sole()->date->toDateString());
    }

    public function test_an_early_morning_vaccine_dose_is_dated_that_day(): void
    {
        $child = $this->patient(['first_name' => 'Nico', 'dob' => '2026-06-01', 'gender' => 'Male', 'registration_type' => 'Child']);
        $childRecord = ChildRecord::create(['patient_id' => $child->id, 'birth_weight_kg' => 3.0, 'birth_height_cm' => 50.0]);
        $dose = Immunization::create([
            'child_record_id' => $childRecord->id,
            'vaccine_name' => 'Pentavalent (DPT-HepB-Hib)',
            'dose_number' => 1,
            'scheduled_date' => '2026-07-13',
            'status' => 'Scheduled',
        ]);

        $this->actingAs($this->adminUser)
            ->post('/immunization?id=' . $child->id, ['immunization_id' => $dose->id])
            ->assertSessionHasNoErrors();

        $this->assertSame('2026-08-20', $dose->fresh()->given_date->toDateString());
    }

    public function test_migration_moves_utc_date_times_to_philippine_time_and_back(): void
    {
        $patient = $this->patient();
        $record = MaternalRecord::create(['patient_id' => $patient->id, 'lmp' => '2026-03-01']);
        DB::table('users')->where('id', $this->adminUser->id)->update([
            'created_at' => '2026-08-19 23:30:00',
            'updated_at' => '2026-08-19 23:30:00',
            'email_verified_at' => null,
        ]);
        DB::table('patients')->where('id', $patient->id)->update(['created_at' => '2026-08-01 02:00:00']);

        $this->runTimezoneMigration('up');

        $user = DB::table('users')->where('id', $this->adminUser->id)->first();
        $this->assertSame('2026-08-20 07:30:00', $user->created_at);
        $this->assertSame('2026-08-20 07:30:00', $user->updated_at);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('2026-08-01 10:00:00', DB::table('patients')->where('id', $patient->id)->value('created_at'));
        // Calendar dates are not moments in time and stay as entered.
        $this->assertStringStartsWith('2026-03-01', DB::table('maternal_records')->where('id', $record->id)->value('lmp'));
        $this->assertStringStartsWith('1998-04-12', DB::table('patients')->where('id', $patient->id)->value('dob'));

        $this->runTimezoneMigration('down');

        $this->assertSame('2026-08-19 23:30:00', DB::table('users')->where('id', $this->adminUser->id)->value('created_at'));
        $this->assertSame('2026-08-01 02:00:00', DB::table('patients')->where('id', $patient->id)->value('created_at'));
    }
}
