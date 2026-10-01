<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GoLiveTest extends TestCase
{
    use RefreshDatabase;

    protected function readyConfig(array $overrides = []): void
    {
        config(array_merge([
            'app.debug' => false,
            'app.env' => 'production',
            'app.url' => 'https://bicao.example.org',
            'session.secure' => true,
            'session.encrypt' => true,
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'a1b2c3d4e5',
            'broadcasting.connections.reverb.secret' => 'f6a7b8c9d0e1',
        ], $overrides));
    }

    public function test_go_live_check_passes_when_the_settings_are_ready(): void
    {
        $this->readyConfig();

        $this->artisan('app:go-live-check')
            ->expectsOutputToContain('No blocking problems')
            ->assertExitCode(0);
    }

    public function test_go_live_check_blocks_debug_mode(): void
    {
        $this->readyConfig(['app.debug' => true]);

        $this->artisan('app:go-live-check')
            ->expectsOutputToContain('Set APP_DEBUG=false')
            ->assertExitCode(1);
    }

    public function test_go_live_check_blocks_demo_accounts_with_the_seeded_password(): void
    {
        $this->readyConfig();
        User::create(['name' => 'Demo Worker', 'email' => 'health@example.com', 'password' => Hash::make('password'), 'role' => 'admin']);

        $this->artisan('app:go-live-check')
            ->expectsOutputToContain('health@example.com')
            ->assertExitCode(1);
    }

    public function test_a_demo_account_with_a_changed_password_is_fine(): void
    {
        $this->readyConfig();
        User::create(['name' => 'Demo Worker', 'email' => 'health@example.com', 'password' => Hash::make('A-real-password-9'), 'role' => 'admin']);

        $this->artisan('app:go-live-check')->assertExitCode(0);
    }

    public function test_go_live_check_blocks_the_example_realtime_keys(): void
    {
        $this->readyConfig([
            'broadcasting.connections.reverb.key' => 'local-reverb-key',
            'broadcasting.connections.reverb.secret' => 'local-reverb-secret',
        ]);

        $this->artisan('app:go-live-check')
            ->expectsOutputToContain('REVERB_APP_KEY')
            ->assertExitCode(1);
    }

    public function test_links_use_https_through_the_local_tunnel(): void
    {
        // The Cloudflare tunnel is a trusted proxy on this machine.
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->assertSee('https://localhost/favicon.ico', false);
    }

    public function test_other_clients_cannot_switch_links_to_https(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
            ->withHeaders(['X-Forwarded-Proto' => 'https'])
            ->get('/')
            ->assertSee('http://localhost/favicon.ico', false)
            ->assertDontSee('https://localhost/favicon.ico', false);
    }
}
