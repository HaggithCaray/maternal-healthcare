<?php

use App\Models\GrowthMeasurement;
use App\Models\MaternalCheckup;
use App\Services\ReminderService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attachments:make-private', function () {
    $public = Storage::disk('public');
    $private = Storage::disk('local');
    $moved = 0;

    foreach ($public->files('attachments') as $path) {
        $private->put($path, $public->get($path));
        $public->delete($path);
        $moved++;
    }

    $this->info("Moved {$moved} chat attachment(s) from public to private storage.");
})->purpose('Move older chat attachments off the publicly served storage disk');

Artisan::command('assessments:recalculate', function () {
    // Saving re-runs the model hooks that derive gestational age, risk flags and WHO z-scores.
    $checkups = 0;
    MaternalCheckup::with('maternalRecord.patient')->chunkById(200, function ($rows) use (&$checkups) {
        $rows->each(fn (MaternalCheckup $checkup) => $checkup->save());
        $checkups += $rows->count();
    });

    $measurements = 0;
    GrowthMeasurement::with('childRecord.patient')->chunkById(200, function ($rows) use (&$measurements) {
        $rows->each(fn (GrowthMeasurement $measurement) => $measurement->save());
        $measurements += $rows->count();
    });

    $this->info("Re-assessed {$checkups} prenatal checkup(s) and {$measurements} growth measurement(s).");
})->purpose('Recompute gestational age, prenatal risk flags and WHO growth z-scores for existing records');

Artisan::command('app:go-live-check', function () {
    $isHttps = str_starts_with((string) config('app.url'), 'https://');
    $usesReverb = config('broadcasting.default') === 'reverb';
    $demoLogins = \App\Models\User::whereIn('email', ['health@example.com', 'patient@example.com'])
        ->get()
        ->filter(fn ($user) => \Illuminate\Support\Facades\Hash::check('password', $user->password))
        ->pluck('email');

    // Newest backup, to warn while the scheduler job is not set up yet or has stopped running.
    // File names are backup-Y-m-d_His.zip, so sorting them by name sorts them by time.
    $latestBackup = is_dir(storage_path('app/backups'))
        ? collect(glob(storage_path('app/backups') . DIRECTORY_SEPARATOR . 'backup-*.zip') ?: [])
            ->sortBy(fn (string $file) => basename($file))
            ->last()
        : null;
    $backupFresh = $latestBackup !== null
        && @filemtime($latestBackup) >= now()->subDays(2)->getTimestamp();

    // [check, ok?, fail (blocks real use) or warn, how to fix]
    $checks = [
        ['Debug mode is off', ! config('app.debug'), 'fail', 'Set APP_DEBUG=false: error pages otherwise show code, settings and patient data.'],
        ['App key is set', filled(config('app.key')), 'fail', 'Run php artisan key:generate.'],
        ['Demo accounts removed', $demoLogins->isEmpty(), 'fail', 'Change the password of, or deactivate: ' . $demoLogins->implode(', ') . '.'],
        ['Real-time chat keys changed', ! $usesReverb || ! in_array(config('broadcasting.connections.reverb.key'), ['local-reverb-key', null, ''], true)
            && ! in_array(config('broadcasting.connections.reverb.secret'), ['local-reverb-secret', null, ''], true),
            'fail', 'Set REVERB_APP_KEY and REVERB_APP_SECRET to random values (php -r "echo bin2hex(random_bytes(16));"), then npm run build.'],
        ['Environment is production', app()->environment('production'), 'warn', 'Set APP_ENV=production.'],
        ['Site address uses HTTPS', $isHttps, 'warn', 'Serve the app over HTTPS and set APP_URL to its https:// address.'],
        ['Session cookie only over HTTPS', (bool) config('session.secure'), 'warn', $isHttps ? 'Set SESSION_SECURE_COOKIE=true.' : 'Once the app is on HTTPS, set SESSION_SECURE_COOKIE=true (not before: sign-in would stop working over plain HTTP).'],
        ['Session data encrypted', (bool) config('session.encrypt'), 'warn', 'Set SESSION_ENCRYPT=true (everyone signs in again once).'],
        ['Log level is not debug', config('logging.channels.' . config('logging.default') . '.level', config('logging.channels.single.level')) !== 'debug', 'warn', 'Set LOG_LEVEL=warning.'],
        ['Configuration is cached', app()->configurationIsCached(), 'warn', 'Run php artisan config:cache after every .env change (php artisan config:clear to undo).'],
        ['Backups are current', $backupFresh, 'warn',
            'Run php artisan backup:run and set up the daily schedule (README, "Scheduled jobs"). Newest found: '
                . ($latestBackup !== null ? basename($latestBackup) : 'none yet') . '.'],
    ];

    $failures = 0;
    $rows = array_map(function (array $check) use (&$failures) {
        [$name, $ok, $level, $fix] = $check;
        if (! $ok && $level === 'fail') {
            $failures++;
        }

        return [$name, $ok ? 'OK' : ($level === 'fail' ? 'FIX FIRST' : 'Recommended'), $ok ? '' : $fix];
    }, $checks);

    $this->table(['Check', 'Status', 'How to fix'], $rows);

    if ($failures > 0) {
        $this->error("{$failures} item(s) must be fixed before patients' data goes on this system.");

        return 1;
    }

    $this->info('No blocking problems. Review any "Recommended" items for this server.');

    return 0;
})->purpose('List settings that must change before the system is used with real patient data');

Artisan::command('reminders:send', function (ReminderService $reminders) {
    $result = $reminders->run();

    // The day's run has happened whatever the outcome — sent, already sent earlier, an
    // empty queue or reminders switched off. The schedule guard reads this marker, so one
    // day texts once and the log carries one line, not one per check. A crash before this
    // line leaves the marker unset and the next quarter-hour simply retries.
    Cache::put('reminders-run:' . now()->toDateString(), true, now()->addDay());

    if (! $result['enabled']) {
        $this->info('Automated reminders are turned off in the SMS settings — nothing sent.');

        return 0;
    }

    $this->info("Reminders: sent {$result['sent']}, failed {$result['failed']}, already sent earlier {$result['skipped']}.");

    return 0;
})->purpose('Text mothers about vaccine doses and prenatal/postpartum visits that are due');

// Nothing runs by itself until something calls schedule:run every minute —
// cron on Linux, Task Scheduler on this Windows server (see README for the command).
//
// The clinic PC is regularly asleep or switched off at 08:00 and 02:00, and Laravel's
// dailyAt() has no catch-up: the minute that was missed is missed for the whole day.
// So each job is checked every quarter hour and runs once at the first opportunity
// after its intended time, guarded so a day can never run it twice.
Schedule::command('reminders:send')
    ->everyFifteenMinutes()
    ->when(fn () => now()->hour >= 8 && ! Cache::has('reminders-run:' . now()->toDateString()))
    ->timezone('Asia/Manila')
    ->withoutOverlapping();

Artisan::command('backup:run', function (\App\Services\BackupService $backup) {
    try {
        $path = $backup->run();
        $this->info("Backup written: {$path}");
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('backup:run failed: ' . $e->getMessage());
        $this->error('Backup failed: ' . $e->getMessage());

        return 1;
    }

    return 0;
})->purpose('Write one zip (database, storage, .env) to storage/app/backups and prune older ones');

// The same catch-up idea as the reminders above, from 02:00 — and the day's own backup
// zip is the "already ran" marker, so no second piece of state is needed.
Schedule::command('backup:run')
    ->everyFifteenMinutes()
    ->when(function () {
        if (now()->hour < 2) {
            return false;
        }

        $today = glob(storage_path('app/backups') . DIRECTORY_SEPARATOR
            . 'backup-' . now()->format('Y-m-d') . '_*.zip');

        return $today === [];
    })
    ->timezone('Asia/Manila')
    ->withoutOverlapping();
