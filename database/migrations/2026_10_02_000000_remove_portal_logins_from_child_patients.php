<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Registration used to give a child its own portal login when an email was entered, or link the
 * child to the login of whoever owned that email. Children don't have logins: the mother sees them
 * through the child record's mother link. This unlinks every child from a login, then:
 * - a login that belongs to a mother: the child is linked to her (if no mother was set), so she
 *   keeps seeing the child in her portal;
 * - a login made only for children: deleted if it was never used, otherwise deactivated so its
 *   messages and history stay intact.
 */
return new class extends Migration
{
    public function up(): void
    {
        $links = DB::table('patients')
            ->where('registration_type', 'Child')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id']);

        if ($links->isEmpty()) {
            return;
        }

        DB::table('patients')->whereIn('id', $links->pluck('id'))->update(['user_id' => null]);

        foreach ($links->groupBy('user_id') as $userId => $children) {
            $mother = DB::table('patients')
                ->where('user_id', $userId)
                ->where('registration_type', 'Maternal')
                ->orderBy('id')
                ->first(['id']);

            if ($mother) {
                DB::table('child_records')
                    ->whereIn('patient_id', $children->pluck('id'))
                    ->whereNull('mother_id')
                    ->update(['mother_id' => $mother->id]);

                continue;
            }

            $user = DB::table('users')->where('id', $userId)->where('role', 'user')->first(['id', 'last_login_at']);
            if (! $user || DB::table('patients')->where('user_id', $userId)->exists()) {
                continue; // a staff account, or still another patient's login: leave it alone
            }

            $used = $user->last_login_at !== null
                || DB::table('chat_messages')->where('sender_id', $userId)->orWhere('receiver_id', $userId)->exists();

            if ($used) {
                DB::table('users')->where('id', $userId)->update(['is_active' => false]);
            } else {
                DB::table('users')->where('id', $userId)->delete();
            }
        }
    }

    public function down(): void
    {
        // Data cleanup only; the removed links are not restored.
    }
};
