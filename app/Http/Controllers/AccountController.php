<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * The signed-in user's own account: changing the temporary password staff handed out.
 */
class AccountController extends Controller
{
    public function editPassword()
    {
        return view('account.password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Your current password is incorrect.',
            'password.different' => 'Choose a password different from your current one.',
        ]);

        $user = $request->user();
        $user->update(['password' => Hash::make($request->password)]);

        // Sign out other devices that still use the old password; keep this one signed in.
        $user->signOutOtherSessions($request->session()->getId());

        AuditLog::log('change_own_password', $user);

        return redirect()->route('account.password')->with('success', 'Your password has been changed.');
    }
}
