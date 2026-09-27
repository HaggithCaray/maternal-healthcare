<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GrowthMeasurement;
use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\Patient;
use App\Models\User;
use App\Services\PrenatalAssessment;
use App\Services\WhoGrowthStandards;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // Display order for the nutrition summary: normal first, then most to least severe.
    protected const NUTRITION_ORDER = [
        WhoGrowthStandards::NORMAL,
        'Severely Wasted',
        'Wasted',
        'Severely Underweight',
        'Underweight',
        'Severely Stunted',
        'Stunted',
        'Obese',
        'Overweight',
        WhoGrowthStandards::RECHECK,
        WhoGrowthStandards::NOT_ASSESSED,
    ];

    /**
     * Display reporting and analytics dashboard for one calendar year.
     */
    public function reports(Request $request)
    {
        $today = Carbon::today();
        $firstRegistration = Patient::min('created_at');
        $firstYear = $firstRegistration ? min(Carbon::parse($firstRegistration)->year, $today->year) : $today->year;
        $years = range($today->year, $firstYear);

        $year = (int) $request->query('year', $today->year);
        if (! in_array($year, $years, true)) {
            $year = $today->year;
        }

        // Current totals
        $totalPatients = Patient::count();
        $maternalCases = Patient::where('registration_type', 'Maternal')->count();
        $childRecords = Patient::where('registration_type', 'Child')->count();
        $highRiskMothers = Patient::where('registration_type', 'Maternal')->where('status', 'High Risk')->count();

        // Registrations per month of the selected year. whereYear() works on both MySQL and SQLite,
        // and grouping in PHP keeps the months of different years from being merged together.
        $monthlyRegistrations = [];
        foreach (range(1, 12) as $month) {
            $monthlyRegistrations[$month] = ['Maternal' => 0, 'Child' => 0];
        }
        Patient::whereYear('created_at', $year)
            ->get(['created_at', 'registration_type'])
            ->each(function (Patient $patient) use (&$monthlyRegistrations) {
                $monthlyRegistrations[$patient->created_at->month][$patient->registration_type]++;
            });
        $registeredInYear = array_sum(array_map('array_sum', $monthlyRegistrations));

        // Immunization coverage: doses scheduled in the selected year that were already due by today.
        // Future appointments are excluded, so they don't drag the rate down.
        $dueDoses = Immunization::whereYear('scheduled_date', $year)->whereDate('scheduled_date', '<=', $today);
        $dosesDue = (clone $dueDoses)->count();
        $dosesGiven = (clone $dueDoses)->where('status', 'Given')->count();
        $complianceRate = $dosesDue > 0 ? (int) round(($dosesGiven / $dosesDue) * 100) : null;
        $overdueDoses = Immunization::where('status', 'Scheduled')->whereDate('scheduled_date', '<', $today)->count();

        // Prenatal care in the selected year
        $prenatalVisits = MaternalCheckup::whereYear('date', $year)->count();
        $highRiskVisits = MaternalCheckup::whereYear('date', $year)->where('status', PrenatalAssessment::HIGH_RISK)->count();

        // Nutritional status of each child at their most recent measurement
        $latestStatuses = GrowthMeasurement::orderByDesc('date')
            ->orderByDesc('id')
            ->get(['child_record_id', 'status'])
            ->unique('child_record_id')
            ->countBy('status');
        $nutritionSummary = collect(self::NUTRITION_ORDER)
            ->mapWithKeys(fn (string $status) => [$status => $latestStatuses->get($status, 0)])
            ->filter(fn (int $count, string $status) => $count > 0 || $status === WhoGrowthStandards::NORMAL)
            ->all();
        $childrenMeasured = $latestStatuses->sum();

        AuditLog::log('view_reports_dashboard', null, [
            'year' => $year,
            'compliance_rate' => $complianceRate,
            'total_patients' => $totalPatients,
        ]);

        return view('reports', compact(
            'year',
            'years',
            'totalPatients',
            'maternalCases',
            'childRecords',
            'highRiskMothers',
            'monthlyRegistrations',
            'registeredInYear',
            'dosesDue',
            'dosesGiven',
            'complianceRate',
            'overdueDoses',
            'prenatalVisits',
            'highRiskVisits',
            'nutritionSummary',
            'childrenMeasured'
        ));
    }

    /**
     * Display admin users management view.
     */
    public function admin()
    {
        $users = User::all();
        AuditLog::log('view_admin_panel');
        return view('admin', compact('users'));
    }
}
