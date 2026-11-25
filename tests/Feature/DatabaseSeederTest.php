<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    private const KEYS = ['ADMIN_EMAIL', 'ADMIN_PASSWORD', 'ADMIN_NAME'];

    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        parent::tearDown();
    }

    private function setEnv(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    public function test_it_creates_only_the_first_admin_from_the_environment(): void
    {
        $this->setEnv('ADMIN_EMAIL', 'first.admin@example.org');
        $this->setEnv('ADMIN_PASSWORD', 'a-long-random-password');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::count());
        $admin = User::firstOrFail();
        $this->assertSame('first.admin@example.org', $admin->email);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('a-long-random-password', $admin->password));
        $this->assertSame(0, Patient::count(), 'No demo patients may be created.');
        $this->assertFalse(User::where('email', 'like', '%@example.com')->exists(), 'No demo accounts.');
    }

    public function test_it_refuses_to_run_without_credentials(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_it_rejects_a_weak_password(): void
    {
        $this->setEnv('ADMIN_EMAIL', 'first.admin@example.org');
        $this->setEnv('ADMIN_PASSWORD', 'password');

        $this->expectException(\RuntimeException::class);

        $this->seed(DatabaseSeeder::class);
    }

    public function test_it_does_nothing_when_an_admin_already_exists(): void
    {
        User::factory()->create(['email' => 'existing@example.org', 'role' => 'admin']);
        $this->setEnv('ADMIN_EMAIL', 'second.admin@example.org');
        $this->setEnv('ADMIN_PASSWORD', 'a-long-random-password');

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertFalse(User::where('email', 'second.admin@example.org')->exists());
    }
}
