<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'role' => ['required', 'in:admin,user'],
        ]);

        $attempt = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'role' => $credentials['role'],
            'is_active' => true,
        ];

        if (Auth::attempt($attempt, $request->boolean('remember'))) {
            $request->session()->regenerate();
            Auth::user()->forceFill(['last_login_at' => now()])->save();

            $redirect = $credentials['role'] === 'user' ? '/portal' : '/dashboard';
            return redirect()->intended($redirect);
        }

        // Same message for a wrong password and a deactivated account, so it doesn't reveal which accounts exist.
        return back()->withErrors([
            'email' => 'The provided credentials or role do not match our records, or the account is deactivated.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function dashboard()
    {
        // The dashboard lists other patients' names and vaccine schedules — staff only.
        if (! auth()->user()->isAdmin()) {
            return redirect()->route('patient.portal');
        }

        $totalMothers = \App\Models\Patient::where('registration_type', 'Maternal')->count();
        $totalChildren = \App\Models\Patient::where('registration_type', 'Child')->count();
        
        $todayVaccinations = \App\Models\Immunization::where('status', 'Scheduled')
            ->whereDate('scheduled_date', \Carbon\Carbon::today())
            ->count();
            
        $unreadMessages = auth()->user()->unreadChatCount();

        // Get upcoming vaccinations for dashboard list
        $upcomingVaccinations = \App\Models\Immunization::with('childRecord.patient')
            ->where('status', 'Scheduled')
            ->orderBy('scheduled_date', 'asc')
            ->take(5)
            ->get();

        // New registrations in each of the last six months (including this one)
        $firstMonth = \Carbon\Carbon::today()->startOfMonth()->subMonths(5);
        $registrations = \App\Models\Patient::where('created_at', '>=', $firstMonth)->get(['created_at', 'registration_type']);
        $chartMonths = [];
        foreach (range(0, 5) as $offset) {
            $month = $firstMonth->copy()->addMonths($offset);
            $inMonth = $registrations->filter(fn ($p) => $p->created_at->isSameMonth($month));
            $chartMonths[] = [
                'label' => $month->format('M Y'),
                'short' => $month->format('M'),
                'Maternal' => $inMonth->where('registration_type', 'Maternal')->count(),
                'Child' => $inMonth->where('registration_type', 'Child')->count(),
            ];
        }

        $recentActivity = $this->recentActivity();

        return view('dashboard', compact('totalMothers', 'totalChildren', 'todayVaccinations', 'unreadMessages', 'upcomingVaccinations', 'chartMonths', 'recentActivity'));
    }

    /**
     * Latest clinical events: new patients, vaccines given and prenatal visits, newest first.
     *
     * @return \Illuminate\Support\Collection<int, array{at: \Carbon\CarbonInterface, text: string, name: string, level: string}>
     */
    protected function recentActivity()
    {
        $patients = \App\Models\Patient::latest()->take(5)->get()->map(fn ($p) => [
            'at' => $p->created_at,
            'text' => 'New ' . strtolower($p->registration_type) . ' record for',
            'name' => $p->full_name,
            'level' => 'info',
        ]);

        $vaccines = \App\Models\Immunization::with('childRecord.patient')
            ->where('status', 'Given')
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn ($i) => [
                'at' => $i->updated_at,
                'text' => "{$i->vaccine_name} dose {$i->dose_number} given to",
                'name' => $i->childRecord?->patient?->full_name ?? 'a child',
                'level' => 'good',
            ]);

        $visits = \App\Models\MaternalCheckup::with('maternalRecord.patient')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($c) => [
                'at' => $c->created_at,
                'text' => $c->status === \App\Services\PrenatalAssessment::HIGH_RISK ? 'High-risk prenatal visit for' : 'Prenatal visit logged for',
                'name' => $c->maternalRecord?->patient?->full_name ?? 'a mother',
                'level' => $c->status === \App\Services\PrenatalAssessment::HIGH_RISK ? 'alert' : 'info',
            ]);

        return $patients->concat($vaccines)->concat($visits)->sortByDesc('at')->take(6)->values();
    }
}
