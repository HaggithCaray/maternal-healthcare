<?php

namespace Tests\Feature;

use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The clinic PC is regularly asleep or switched off at the two scheduled times, and a
 * dailyAt() schedule has no catch-up — a missed minute is missed for the whole day. Both
 * jobs are therefore checked every quarter hour with a guard that lets each run exactly
 * once, at the first opportunity after 08:00 (reminders) or 02:00 (backup).
 */
class ScheduleCatchUpTest extends TestCase
{
    private string $settingsPath;

    /** Whatever gateway configuration was on this machine before the test, put back after. */
    private ?string $originalSettings = null;

    /** Every dummy zip this test wrote, removed again afterwards. */
    private array $createdZips = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsPath = storage_path('app/sms_settings.json');
        $this->originalSettings = file_exists($this->settingsPath) ? file_get_contents($this->settingsPath) : null;

        // The off switch, deterministically: this suite's reminder runs never text anyone.
        app(SmsService::class)->saveSettings('https://api.sms-gate.app/3rdparty/v1', 'gw-user', 'gw-pass', false);
    }

    protected function tearDown(): void
    {
        $this->originalSettings === null
            ? @unlink($this->settingsPath)
            : file_put_contents($this->settingsPath, $this->originalSettings);

        foreach ($this->createdZips as $zip) {
            @unlink($zip);
        }

        parent::tearDown();
    }

    /** The single scheduled entry whose command line mentions the given command. */
    private function event(string $command): Event
    {
        $events = array_values(array_filter(
            app(Schedule::class)->events(),
            fn (Event $event) => str_contains((string) $event->command, $command)
        ));

        $this->assertCount(1, $events, "Expected exactly one scheduled entry for {$command}.");

        return $events[0];
    }

    /** Whether the schedule's guards let the entry through at the given moment. */
    private function guardsPass(string $time, string $command): bool
    {
        Carbon::setTestNow($time);

        return $this->event($command)->filtersPass($this->app);
    }

    public function test_reminders_are_checked_every_quarter_hour_and_catch_up_after_0800(): void
    {
        $this->assertSame('*/15 * * * *', $this->event('reminders:send')->expression);

        $this->assertFalse($this->guardsPass('2026-10-17 07:59:00', 'reminders:send'), 'before 08:00');
        $this->assertTrue($this->guardsPass('2026-10-17 08:15:00', 'reminders:send'), 'the first check after 08:00');

        // The PC slept all day and was switched on at 22:30: the day's run is still owed.
        $this->assertTrue($this->guardsPass('2026-10-17 22:45:00', 'reminders:send'), 'catch-up later the same day');
    }

    public function test_the_reminders_run_happens_once_a_day(): void
    {
        Carbon::setTestNow('2026-10-17 08:15:00');
        $this->assertTrue($this->event('reminders:send')->filtersPass($this->app));

        // Reminders are off in this suite, so this also proves that "switched off" still
        // counts as the day's run — one log line, not one per quarter hour.
        $this->artisan('reminders:send')
            ->expectsOutputToContain('turned off')
            ->assertExitCode(0);

        $this->assertFalse($this->guardsPass('2026-10-17 08:30:00', 'reminders:send'), 'already run today');
    }

    public function test_yesterdays_reminders_marker_does_not_block_todays_run(): void
    {
        Carbon::setTestNow('2026-10-16 09:00:00');
        $this->artisan('reminders:send')->assertExitCode(0);

        $this->assertTrue($this->guardsPass('2026-10-17 08:15:00', 'reminders:send'));
    }

    public function test_backups_are_checked_every_quarter_hour_and_catch_up_after_0200(): void
    {
        $this->assertSame('*/15 * * * *', $this->event('backup:run')->expression);

        $this->assertFalse($this->guardsPass('2026-10-17 01:59:00', 'backup:run'), 'before 02:00');
        $this->assertTrue($this->guardsPass('2026-10-17 02:15:00', 'backup:run'), 'the first check after 02:00');

        // The PC slept through the night and was switched on in the afternoon: still owed.
        $this->assertTrue($this->guardsPass('2026-10-17 13:15:00', 'backup:run'), 'catch-up later the same day');
    }

    public function test_the_backup_guard_stops_once_todays_backup_exists(): void
    {
        // Registered before writing the file, so it is cleaned up even if an assertion fails.
        $zip = config('backup.path') . DIRECTORY_SEPARATOR . 'backup-2026-10-17_020500.zip';
        $this->createdZips[] = $zip;
        File::ensureDirectoryExists(dirname($zip));
        File::put($zip, 'dummy');

        $this->assertFalse($this->guardsPass('2026-10-17 13:15:00', 'backup:run'), 'today already has a backup');

        // Yesterday's backup must not swallow today's.
        $this->assertTrue($this->guardsPass('2026-10-18 02:15:00', 'backup:run'), 'a new day is owed a new backup');
    }
}
