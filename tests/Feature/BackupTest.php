<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    /** A marker file inside storage/app, so the test can prove those files are archived. */
    private string $probeDir;

    /** Every zip this test wrote, removed again afterwards. */
    private array $createdZips = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-08-20 02:00:00');

        $this->probeDir = storage_path('app/backup-probe');
        File::makeDirectory($this->probeDir, 0755, true, true);
        File::put($this->probeDir . '/marker.txt', 'attachment contents');
    }

    protected function tearDown(): void
    {
        foreach ($this->createdZips as $zip) {
            @unlink($zip);
        }

        File::deleteDirectory($this->probeDir);

        parent::tearDown();
    }

    private function backup(): string
    {
        $path = app(BackupService::class)->run();
        $this->createdZips[] = $path;

        return $path;
    }

    private function mother(): Patient
    {
        return Patient::create([
            'first_name' => 'Backupme',
            'last_name' => 'Cruz',
            'dob' => '1995-01-01',
            'gender' => 'Female',
            'phone' => '09171234567',
            'address' => 'Purok 1',
            'barangay' => 'Bicao',
            'emergency_contact_name' => 'Juan Cruz',
            'emergency_contact_phone' => '09222222222',
            'registration_type' => 'Maternal',
            'status' => 'Active',
        ]);
    }

    /** @return \ZipArchive the opened archive */
    private function openZip(string $path): \ZipArchive
    {
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path));

        return $zip;
    }

    public function test_a_backup_holds_the_database_the_env_and_storage(): void
    {
        $this->mother();

        $path = $this->backup();

        $this->assertFileExists($path);
        $this->assertStringStartsWith('backup-2026-08-20_020000.zip', basename($path));

        $zip = $this->openZip($path);

        $this->assertNotFalse($zip->locateName('database.sql.gz'), 'database dump missing');
        $this->assertNotFalse($zip->locateName('.env'), '.env missing');
        $this->assertNotFalse($zip->locateName('storage/app/backup-probe/marker.txt'), 'storage file missing');

        // The backups folder is never archived into itself.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $this->assertStringStartsNotWith('storage/app/backups/', $name, "backup nested inside backup: {$name}");
        }

        $zip->close();

        $dump = $this->dumpFrom($path);
        $this->assertMatchesRegularExpression('/create table "patients"/i', $dump);
        $this->assertStringContainsString('INSERT INTO "patients"', $dump);
        $this->assertStringContainsString('Backupme', $dump);
        // sqlite_sequence is auto-created on replay, so its own CREATE TABLE must stay out.
        $this->assertStringNotContainsString('sqlite_sequence', $dump);
    }

    public function test_the_dump_replays_into_a_working_database(): void
    {
        $this->mother();
        $path = $this->backup();

        $dump = $this->dumpFrom($path);
        $target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'replay-' . bin2hex(random_bytes(4)) . '.sqlite';
        $replayed = null;

        try {
            $replayed = new \PDO('sqlite:' . $target);
            $replayed->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $replayed->exec($dump);

            // A column added later by ALTER TABLE must be in the dump too.
            $columns = $replayed->query('PRAGMA table_info(maternal_records)')->fetchAll(\PDO::FETCH_COLUMN, 1);
            $this->assertContains('delivery_date', $columns);

            $name = $replayed->query("SELECT first_name FROM patients WHERE last_name = 'Cruz'")->fetchColumn();
            $this->assertSame('Backupme', $name);
        } finally {
            $replayed = null;
            @unlink($target);
        }
    }

    public function test_backups_older_than_thirty_days_are_pruned(): void
    {
        $first = $this->backup();

        // Make the first backup look a month old, then run again.
        touch($first, Carbon::now()->subDays(31)->getTimestamp());
        Carbon::setTestNow(Carbon::now()->addDay());

        $second = $this->backup();

        $this->assertFileDoesNotExist($first);
        $this->assertFileExists($second);
    }

    public function test_the_backup_command_writes_a_zip(): void
    {
        // Registered before asserting, so it is cleaned up even if the assertions fail.
        $zip = config('backup.path') . DIRECTORY_SEPARATOR . 'backup-2026-08-20_020000.zip';
        $this->createdZips[] = $zip;

        $this->artisan('backup:run')
            ->expectsOutputToContain('Backup written:')
            ->assertExitCode(0);

        $this->assertFileExists($zip);
    }

    public function test_the_backup_command_reports_a_failed_backup(): void
    {
        // A driver the service refuses to dump: the failure must be reported, not thrown.
        // The default is restored immediately — the test's own cleanup resolves it.
        $original = config('database.default');
        config(['database.default' => 'pgsql']);

        try {
            $this->artisan('backup:run')
                ->expectsOutputToContain('Backup failed:')
                ->assertExitCode(1);
        } finally {
            config(['database.default' => $original]);
        }
    }

    public function test_with_a_password_every_file_in_the_backup_is_encrypted(): void
    {
        config(['backup.password' => 'correct horse battery']);
        $this->mother();

        $path = $this->backup();

        // Nothing readable in the raw file, and nothing readable without the password.
        $this->assertStringNotContainsString('attachment contents', file_get_contents($path));
        $zip = $this->openZip($path);
        $this->assertFalse($zip->getFromName('storage/app/backup-probe/marker.txt'));
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (! str_ends_with($zip->getNameIndex($i), '/')) {
                $this->assertSame(\ZipArchive::EM_AES_256, $zip->statIndex($i)['encryption_method'], $zip->getNameIndex($i));
            }
        }
        $zip->close();

        $zip = $this->openZip($path);
        $zip->setPassword('correct horse battery');
        $this->assertSame('attachment contents', $zip->getFromName('storage/app/backup-probe/marker.txt'));
        $this->assertStringContainsString('Backupme', (string) gzdecode($zip->getFromName('database.sql.gz')));
        $zip->close();
    }

    public function test_each_backup_is_copied_to_the_second_folder(): void
    {
        $copyDir = storage_path('app/backup-probe/usb');
        config(['backup.copy_to' => $copyDir]);

        $path = $this->backup();

        $this->assertFileExists($copyDir . DIRECTORY_SEPARATOR . basename($path));
        $this->assertFileEquals($path, $copyDir . DIRECTORY_SEPARATOR . basename($path));
    }

    public function test_a_missing_copy_folder_does_not_fail_the_backup(): void
    {
        // A drive letter that is not plugged in.
        config(['backup.copy_to' => 'Q:\\no-such-drive\\backups']);

        $this->assertFileExists($this->backup());
    }

    public function test_latest_backup_and_whether_it_is_current(): void
    {
        $service = app(BackupService::class);
        $this->assertNull($service->latest());
        $this->assertFalse($service->isCurrent());

        $path = $this->backup();
        $this->assertSame(basename($path), $service->latest()['name']);
        $this->assertTrue($service->isCurrent());

        touch($path, Carbon::now()->subDays(3)->getTimestamp());
        $this->assertFalse($service->isCurrent());
    }

    public function test_the_admin_page_warns_when_backups_have_stopped(): void
    {
        $admin = \App\Models\User::create(['name' => 'Rosa', 'email' => 'rosa@health.test', 'password' => \Illuminate\Support\Facades\Hash::make('secret123'), 'role' => 'admin']);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('No backup has been made yet.');

        $path = $this->backup();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertDontSee('No backup');

        touch($path, Carbon::now()->subDays(8)->getTimestamp());
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('No backup in 1 week');
    }

    public function test_go_live_check_requires_a_backup_password(): void
    {
        config(['backup.password' => null]);
        $this->artisan('app:go-live-check')->expectsOutputToContain('Set BACKUP_PASSWORD');
    }

    public function test_go_live_check_warns_when_backups_are_missing(): void
    {
        $this->artisan('app:go-live-check')
            ->expectsOutputToContain('Backups');
    }

    /** The gzipped dump stored in a backup zip, decoded. */
    private function dumpFrom(string $zipPath): string
    {
        $zip = $this->openZip($zipPath);
        $raw = $zip->getFromName('database.sql.gz');
        $zip->close();

        $this->assertNotFalse($raw, 'database dump missing');

        return (string) gzdecode($raw);
    }
}
