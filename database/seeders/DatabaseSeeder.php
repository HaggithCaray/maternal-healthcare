<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Production-safe seeder: it creates the FIRST admin account and nothing else.
 *
 * No demo patients, no demo accounts and no default password. The admin's email and password
 * are read from the environment so they never live in the repository:
 *
 *     ADMIN_EMAIL=you@example.com ADMIN_PASSWORD='a-long-random-password' php artisan db:seed
 *
 * (ADMIN_NAME is optional.) It does nothing when an admin already exists, so running it again
 * on a live system is harmless. For sample data on a development machine use:
 *
 *     php artisan db:seed --class=DemoSeeder
 */
class DatabaseSeeder extends Seeder
{
    /** Shortest password accepted for the first admin. */
    private const MIN_PASSWORD_LENGTH = 12;

    public function run(): void
    {
        if (User::where('role', 'admin')->exists()) {
            $this->command?->info('An admin account already exists; nothing seeded.');

            return;
        }

        $email = trim((string) env('ADMIN_EMAIL'));
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Set ADMIN_EMAIL to a valid email address before seeding the first admin.');
        }

        if (strlen($password) < self::MIN_PASSWORD_LENGTH || strtolower($password) === 'password') {
            throw new \RuntimeException('Set ADMIN_PASSWORD to at least ' . self::MIN_PASSWORD_LENGTH . ' characters before seeding the first admin.');
        }

        User::create([
            'name' => trim((string) env('ADMIN_NAME')) ?: 'Health Worker',
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->command?->info("First admin created: {$email}");
    }
}
