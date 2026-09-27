<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Account management for healthcare workers (staff and patient portal logins) and the audit trail.
 */
class AdminController extends Controller
{
    /**
     * User list with search and filters, plus recent activity.
     */
    public function index(Request $request, SmsService $smsService)
    {
        $search = trim((string) $request->query('search', ''));
        $role = $request->query('role', 'all');
        $status = $request->query('status', 'all');

        $users = User::query()
            ->with('patient:id,user_id,first_name,last_name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            })
            ->when(in_array($role, ['admin', 'user'], true), fn ($query) => $query->where('role', $role))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'staff' => User::where('role', 'admin')->count(),
            'patients' => User::where('role', 'user')->count(),
            'inactive' => User::where('is_active', false)->count(),
        ];

        $recentActivity = AuditLog::with('user:id,name')->latest('id')->take(8)->get();

        $system = [
            'PHP' => PHP_VERSION,
            'Laravel' => app()->version(),
            'Database' => DB::connection()->getDriverName(),
            'Real-time chat' => config('broadcasting.default') === 'reverb' ? 'Reverb' : 'Off (' . config('broadcasting.default') . ')',
            'SMS gateway' => $smsService->getSettings()['url'] ? 'Configured' : 'Not configured',
            'Patients on record' => number_format(Patient::count()),
        ];

        AuditLog::log('view_admin_panel');

        return view('admin', compact('users', 'counts', 'recentActivity', 'system', 'search', 'role', 'status'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Create a healthcare worker account and show its temporary password once.
     * Patient portal accounts are created from patient registration instead.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
        ]);

        $temporaryPassword = User::temporaryPassword();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => 'admin',
        ]);

        AuditLog::log('create_staff_account', $user, ['email' => $user->email]);

        return redirect()->route('admin')
            ->with('success', "Healthcare worker account created for {$user->name}.")
            ->with('portal_credentials', $this->credentials($user, $temporaryPassword));
    }

    public function edit(User $user)
    {
        $user->load('patient');
        $activity = AuditLog::where('user_id', $user->id)->latest('id')->take(10)->get();

        return view('admin.users.edit', compact('user', 'activity'));
    }

    /**
     * Update a staff account's name and email. Patient accounts are edited through Edit Patient,
     * which keeps the patient record and the login in step.
     */
    public function update(Request $request, User $user)
    {
        if (! $user->isAdmin()) {
            return redirect()->route('admin.users.edit', $user)
                ->with('error', 'Patient accounts are updated from the Edit Patient page.');
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($data);
        AuditLog::log('update_staff_account', $user, ['email' => $user->email]);

        return redirect()->route('admin.users.edit', $user)->with('success', 'Account details saved.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $temporaryPassword = User::temporaryPassword();
        $user->update(['password' => Hash::make($temporaryPassword)]);
        $user->signOutOtherSessions($user->is($request->user()) ? $request->session()->getId() : null);

        AuditLog::log('reset_account_password', $user);

        return redirect()->route('admin.users.edit', $user)
            ->with('portal_credentials', $this->credentials($user, $temporaryPassword));
    }

    /**
     * Activate or deactivate an account. Staff can't deactivate themselves, which also means
     * the last active staff account can never be locked out.
     */
    public function toggleStatus(Request $request, User $user)
    {
        if ($user->is_active && $user->is($request->user())) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            $user->signOutOtherSessions();
        }
        AuditLog::log($user->is_active ? 'activate_account' : 'deactivate_account', $user);

        return back()->with('success', $user->is_active
            ? "{$user->name} can sign in again."
            : "{$user->name} has been deactivated and can no longer sign in.");
    }

    /**
     * Full audit trail, newest first.
     */
    public function activity(Request $request)
    {
        $userId = $request->query('user');

        $logs = AuditLog::with('user:id,name,role')
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        $filterUser = $userId ? User::find($userId) : null;

        return view('admin.activity', compact('logs', 'filterUser'));
    }

    /**
     * @return array{name: string, email: string, password: string, role: string}
     */
    protected function credentials(User $user, string $password): array
    {
        return ['name' => $user->name, 'email' => $user->email, 'password' => $password, 'role' => $user->role];
    }
}
