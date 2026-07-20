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

        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'role' => $credentials['role']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            $redirect = $credentials['role'] === 'user' ? '/portal' : '/dashboard';
            return redirect()->intended($redirect);
        }

        return back()->withErrors([
            'email' => 'The provided credentials or role do not match our records.',
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
        $totalMothers = \App\Models\Patient::where('registration_type', 'Maternal')->count();
        $totalChildren = \App\Models\Patient::where('registration_type', 'Child')->count();
        
        $todayVaccinations = \App\Models\Immunization::where('status', 'Scheduled')
            ->whereDate('scheduled_date', \Carbon\Carbon::today())
            ->count();
            
        $unreadMessages = \App\Models\ChatMessage::where('receiver_id', auth()->user()->id)
            ->where('is_read', false)
            ->count();

        // Get upcoming vaccinations for dashboard list
        $upcomingVaccinations = \App\Models\Immunization::with('childRecord.patient')
            ->where('status', 'Scheduled')
            ->orderBy('scheduled_date', 'asc')
            ->take(5)
            ->get();

        return view('dashboard', compact('totalMothers', 'totalChildren', 'todayVaccinations', 'unreadMessages', 'upcomingVaccinations'));
    }
}
