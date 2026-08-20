<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display reporting and analytics dashboard.
     */
    public function reports()
    {
        $totalPatients = Patient::count();
        $maternalCases = Patient::where('registration_type', 'Maternal')->count();
        $childRecords = Patient::where('registration_type', 'Child')->count();

        // Monthly registrations breakdown (database-agnostic)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $registrations = Patient::selectRaw('strftime("%m", created_at) as month, count(*) as count')
                                    ->groupBy('month')
                                    ->orderBy('month')
                                    ->get();
        } else {
            $registrations = Patient::selectRaw('DATE_FORMAT(created_at, "%m") as month, count(*) as count')
                                    ->groupBy('month')
                                    ->orderBy('month')
                                    ->get();
        }

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyData = array_fill_keys($months, 0);

        foreach ($registrations as $reg) {
            $monthIndex = intval($reg->month) - 1;
            if ($monthIndex >= 0 && $monthIndex < 12) {
                $monthlyData[$months[$monthIndex]] = $reg->count;
            }
        }

        $totalDoses = Immunization::count();
        $administeredDoses = Immunization::where('status', 'Given')->count();
        $complianceRate = $totalDoses > 0 ? round(($administeredDoses / $totalDoses) * 100) : 0;

        AuditLog::log('view_reports_dashboard', null, [
            'compliance_rate' => $complianceRate,
            'total_patients' => $totalPatients,
        ]);

        return view('reports', compact(
            'totalPatients',
            'maternalCases',
            'childRecords',
            'monthlyData',
            'complianceRate'
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
