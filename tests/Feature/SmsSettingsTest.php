<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The gateway password is encrypted at rest and never sent back to the page.
 */
class SmsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private string $settingsPath;

    private ?string $original = null;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['status' => 'pass'], 200)]);

        $this->settingsPath = storage_path('app/sms_settings.json');
        $this->original = file_exists($this->settingsPath) ? file_get_contents($this->settingsPath) : null;

        $this->admin = User::create(['name' => 'Rosa', 'email' => 'rosa@health.test', 'password' => Hash::make('secret123'), 'role' => 'admin']);
        app(SmsService::class)->saveSettings('https://api.sms-gate.app/3rdparty/v1', 'gw-user', 'gw-secret-pass');
    }

    protected function tearDown(): void
    {
        $this->original === null
            ? @unlink($this->settingsPath)
            : file_put_contents($this->settingsPath, $this->original);

        parent::tearDown();
    }

    private function saved(): array
    {
        return app(SmsService::class)->getSettings();
    }

    public function test_the_saved_password_is_never_written_into_the_page(): void
    {
        $this->actingAs($this->admin)->get('/sms')
            ->assertOk()
            ->assertDontSee('gw-secret-pass')
            ->assertSee('Saved (blank keeps it)')
            ->assertSee('value="gw-user"', false);
    }

    public function test_a_blank_password_keeps_the_saved_one_for_the_same_gateway(): void
    {
        $this->actingAs($this->admin)->from('/sms')->post('/sms/settings', [
            'url' => 'https://api.sms-gate.app/3rdparty/v1/',
            'username' => 'gw-user',
            'password' => '',
            'reminders_enabled' => '1',
        ])->assertSessionHas('success');

        $this->assertSame('gw-secret-pass', $this->saved()['password']);
        $this->assertTrue($this->saved()['reminders_enabled']);
    }

    public function test_a_new_gateway_host_needs_the_password_again(): void
    {
        $this->actingAs($this->admin)->from('/sms')->post('/sms/settings', [
            'url' => 'https://evil.example.com/collect',
            'username' => 'gw-user',
            'password' => '',
        ])->assertRedirect('/sms')->assertSessionHasErrors('password');

        // Nothing changed, so the saved password was never pointed at the new host.
        $this->assertSame('https://api.sms-gate.app/3rdparty/v1', $this->saved()['url']);
        $this->assertSame('gw-secret-pass', $this->saved()['password']);
    }

    public function test_a_new_password_replaces_the_saved_one(): void
    {
        $this->actingAs($this->admin)->from('/sms')->post('/sms/settings', [
            'url' => 'http://192.168.1.100:8080',
            'username' => 'phone-user',
            'password' => 'phone-pass',
        ])->assertSessionHas('success');

        $this->assertSame('http://192.168.1.100:8080', $this->saved()['url']);
        $this->assertSame('phone-pass', $this->saved()['password']);
    }
}
