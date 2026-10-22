<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One dated .zip in storage/app/backups with everything needed to rebuild the system:
 * a gzipped database dump, the private storage (chat attachments and the encrypted SMS
 * gateway settings) and the .env. Backups older than 30 days are pruned after each run.
 *
 * With BACKUP_PASSWORD set, every file in the zip is AES-256 encrypted; with
 * BACKUP_COPY_PATH set, each zip is also copied there (see config/backup.php).
 *
 * On MySQL the dump comes from mysqldump — set MYSQLDUMP_BINARY in .env when it is not
 * on PATH (with XAMPP: C:\xampp\mysql\bin\mysqldump.exe). SQLite databases are written
 * out as SQL instead, because VACUUM INTO refuses to run inside a transaction.
 */
class BackupService
{
    /** How long each backup is kept, in days. */
    public const RETENTION_DAYS = 30;

    /**
     * Write one backup and prune expired ones.
     *
     * @return string the path of the zip that was written
     *
     * @throws \RuntimeException when the dump or the archive cannot be written
     */
    public function run(): string
    {
        $dir = $this->directory();

        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException("The backup folder {$dir} does not exist and could not be created.");
        }

        $zipPath = $dir . DIRECTORY_SEPARATOR . 'backup-' . now()->format('Y-m-d_His') . '.zip';
        $dumpPath = null;

        try {
            [$dumpPath, $dumpEntry] = $this->dumpDatabase();

            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException("Cannot open {$zipPath} for writing.");
            }

            $zip->addFile($dumpPath, $dumpEntry);
            // The dump is gzipped already, so store it instead of deflating it twice.
            $zip->setCompressionName($dumpEntry, \ZipArchive::CM_STORE);

            if (is_file(base_path('.env'))) {
                $zip->addFile(base_path('.env'), '.env');
            }

            $this->addStorageFiles($zip);
            $this->encrypt($zip);

            if (! $zip->close()) {
                throw new \RuntimeException("Could not finish writing {$zipPath}.");
            }
        } finally {
            if ($dumpPath !== null && is_file($dumpPath)) {
                @unlink($dumpPath);
            }
        }

        $pruned = $this->prune($dir);

        Log::info('Backup written: ' . basename($zipPath)
            . ($pruned > 0 ? " (pruned {$pruned} older than " . self::RETENTION_DAYS . ' days)' : '')
            . ($this->isEncrypted() ? '' : ' WITHOUT a password: set BACKUP_PASSWORD in .env'));

        $this->copyElsewhere($zipPath);

        return $zipPath;
    }

    /**
     * The folder the zips are written to (config backup.path), without a trailing slash.
     */
    public function directory(): string
    {
        return rtrim(str_replace('/', DIRECTORY_SEPARATOR, (string) config('backup.path')), '\/');
    }

    public function isEncrypted(): bool
    {
        return filled(config('backup.password'));
    }

    /**
     * The newest backup on this PC, or null when there is none.
     *
     * @return array{name: string, path: string, at: Carbon}|null
     */
    public function latest(): ?array
    {
        // Names are backup-Y-m-d_His.zip, so sorting by name sorts by time.
        $newest = collect(glob($this->directory() . DIRECTORY_SEPARATOR . 'backup-*.zip') ?: [])
            ->sortBy(fn (string $file) => basename($file))
            ->last();

        return $newest === null ? null : [
            'name' => basename($newest),
            'path' => $newest,
            'at' => Carbon::createFromTimestamp((int) @filemtime($newest)),
        ];
    }

    /**
     * Whether a backup was written within the last two days (the scheduler runs daily).
     */
    public function isCurrent(): bool
    {
        $latest = $this->latest();

        return $latest !== null && $latest['at']->greaterThanOrEqualTo(now()->subDays(2));
    }

    /**
     * AES-256 every entry when a backup password is configured.
     *
     * @throws \RuntimeException when this PHP's zip extension cannot encrypt
     */
    protected function encrypt(\ZipArchive $zip): void
    {
        if (! $this->isEncrypted()) {
            return;
        }

        if (! \ZipArchive::isEncryptionMethodSupported(\ZipArchive::EM_AES_256)) {
            throw new \RuntimeException('BACKUP_PASSWORD is set but this PHP zip extension cannot encrypt with AES-256.');
        }

        $zip->setPassword((string) config('backup.password'));

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (! str_ends_with((string) $zip->getNameIndex($i), '/')
                && ! $zip->setEncryptionIndex($i, \ZipArchive::EM_AES_256)) {
                throw new \RuntimeException('Could not encrypt ' . $zip->getNameIndex($i) . ' in the backup.');
            }
        }
    }

    /**
     * Copy the zip to BACKUP_COPY_PATH (a USB drive, a synced cloud folder) and prune old
     * copies there too. A failed copy is logged, not fatal: the local backup is still good.
     */
    protected function copyElsewhere(string $zipPath): void
    {
        $target = trim((string) config('backup.copy_to'));
        if ($target === '') {
            return;
        }

        $target = rtrim($target, '\/');

        if ((! is_dir($target) && ! @mkdir($target, 0755, true) && ! is_dir($target))
            || ! @copy($zipPath, $target . DIRECTORY_SEPARATOR . basename($zipPath))) {
            Log::error("Backup written, but copying it to {$target} failed. Check that the drive or folder is available.");

            return;
        }

        $this->prune($target);
        Log::info('Backup copied to ' . $target);
    }

    /**
     * @return array{0: string, 1: string} [temporary gzipped dump, its name inside the zip]
     *
     * @throws \RuntimeException when the dump fails
     */
    protected function dumpDatabase(): array
    {
        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        return match ($driver) {
            'mysql' => $this->dumpMysql(),
            'sqlite' => $this->dumpSqlite(),
            default => throw new \RuntimeException("Backups are not supported for the \"{$driver}\" database driver."),
        };
    }

    /**
     * Stream mysqldump straight into a gzipped file, so a large database is never held
     * in memory. The password is passed through MYSQL_PWD rather than the command line.
     *
     * @throws \RuntimeException when mysqldump is missing or exits with an error
     */
    protected function dumpMysql(): array
    {
        $connection = (string) config('database.default');
        $config = (array) config("database.connections.{$connection}");
        $binary = $this->resolveMysqldump();
        $path = $this->tempPath();
        $errorPath = $path . '.err';

        $command = [
            $binary,
            '--host=' . $config['host'],
            '--port=' . (string) (int) ($config['port'] ?? 3306),
            '--user=' . $config['username'],
            '--default-character-set=' . ($config['charset'] ?? 'utf8mb4'),
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            '--hex-blob',
            '--add-drop-table',
            (string) $config['database'],
        ];

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            // A file rather than a pipe: a chatty stderr can never block the dump.
            2 => ['file', $errorPath, 'w'],
        ];

        $environment = array_merge(getenv(), ['MYSQL_PWD' => (string) ($config['password'] ?? '')]);
        $process = @proc_open($command, $descriptors, $pipes, null, $environment);

        if (! is_resource($process)) {
            @unlink($path);

            throw new \RuntimeException("Could not start mysqldump ({$binary}).");
        }

        fclose($pipes[0]);

        $out = gzopen($path, 'wb');
        while (! feof($pipes[1])) {
            $chunk = fread($pipes[1], 1048576);
            if ($chunk !== false && $chunk !== '') {
                gzwrite($out, $chunk);
            }
        }
        fclose($pipes[1]);
        gzclose($out);

        $exit = proc_close($process);
        $error = trim((string) @file_get_contents($errorPath));
        @unlink($errorPath);

        if ($exit !== 0 || ! is_file($path) || filesize($path) === 0) {
            @unlink($path);

            throw new \RuntimeException('mysqldump failed: ' . ($error !== '' ? $error : "exit code {$exit}"));
        }

        return [$path, 'database.sql.gz'];
    }

    /**
     * Write the SQLite schema and rows out as SQL. VACUUM INTO would give a tidier binary
     * copy, but it cannot run inside a transaction, and a second connection to an
     * in-memory database would see nothing — this works in every environment.
     *
     * @throws \RuntimeException when the dump file cannot be written
     */
    protected function dumpSqlite(): array
    {
        $path = $this->tempPath();
        $out = gzopen($path, 'wb');

        if ($out === false) {
            throw new \RuntimeException("Cannot write the database dump to {$path}.");
        }

        $pdo = DB::connection()->getPdo();

        try {
            gzwrite($out, '-- Maternal Health Hub database dump, ' . now()->toDateTimeString() . "\n\n");

            $objects = $pdo->query('SELECT type, name, sql FROM sqlite_master WHERE sql IS NOT NULL ORDER BY rowid')
                ->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($objects as $object) {
                // sqlite_sequence is created automatically by the first AUTOINCREMENT table,
                // so replaying its CREATE TABLE would fail; its rows follow from the data.
                if ($object['name'] === 'sqlite_sequence') {
                    continue;
                }

                gzwrite($out, rtrim($object['sql'], " \t\r\n") . ";\n");
            }

            gzwrite($out, "\n");

            foreach ($objects as $object) {
                if ($object['type'] === 'table' && $object['name'] !== 'sqlite_sequence') {
                    $this->dumpSqliteTable($out, $pdo, $object['name']);
                }
            }
        } finally {
            gzclose($out);
        }

        return [$path, 'database.sql.gz'];
    }

    /**
     * Append one INSERT per row, streaming straight into the dump.
     *
     * @param  resource  $out
     */
    protected function dumpSqliteTable($out, \PDO $pdo, string $table): void
    {
        $statement = $pdo->query('SELECT * FROM ' . $this->quoteIdentifier($table));

        while (($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $columns = array_map(fn (string $column) => $this->quoteIdentifier($column), array_keys($row));
            $values = array_map(fn ($value) => $this->quoteValue($value), array_values($row));

            gzwrite($out, 'INSERT INTO ' . $this->quoteIdentifier($table)
                . ' (' . implode(', ', $columns) . ') VALUES ('
                . implode(', ', $values) . ");\n");
        }

        $statement->closeCursor();
    }

    /**
     * Add everything under storage/app except the backups folder itself, so a backup
     * never carries the older backups (or, worse, itself).
     */
    protected function addStorageFiles(\ZipArchive $zip): void
    {
        $root = rtrim(storage_path('app'), '\\/');
        if (! is_dir($root)) {
            return;
        }

        // The configured folder, and the default one in case BACKUP_PATH moved it: a backup
        // never carries other backups.
        $excluded = array_unique([$this->directory(), $root . DIRECTORY_SEPARATOR . 'backups']);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();

            $normalised = str_replace('/', DIRECTORY_SEPARATOR, $path);
            $skip = false;
            foreach ($excluded as $dir) {
                $skip = $skip || $normalised === $dir || str_starts_with($normalised, $dir . DIRECTORY_SEPARATOR);
            }
            if ($skip) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));

            if ($item->isDir()) {
                $zip->addEmptyDir('storage/app/' . $relative);
            } else {
                $zip->addFile($path, 'storage/app/' . $relative);
            }
        }
    }

    /**
     * Delete backups older than the retention window; returns how many went.
     */
    protected function prune(string $dir): int
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS)->getTimestamp();
        $pruned = 0;

        foreach (glob(rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . 'backup-*.zip') ?: [] as $file) {
            if (@filemtime($file) < $cutoff) {
                $pruned += @unlink($file) ? 1 : 0;
            }
        }

        return $pruned;
    }

    /**
     * mysqldump from MYSQLDUMP_BINARY when set, otherwise a bare name on PATH, otherwise
     * the usual XAMPP install location.
     *
     * @throws \RuntimeException when it cannot be found anywhere
     */
    protected function resolveMysqldump(): string
    {
        $candidates = array_unique(array_filter([
            trim((string) config('database.mysqldump_binary')),
            'mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'D:\\xampp\\mysql\\bin\\mysqldump.exe',
        ]));

        foreach ($candidates as $candidate) {
            if ($this->isRunnable($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException(
            'mysqldump was not found. Set MYSQLDUMP_BINARY in .env to its full path '
            . '(with XAMPP: C:\\xampp\\mysql\\bin\\mysqldump.exe).'
        );
    }

    /**
     * A path has to exist; a bare command has to be findable along PATH.
     */
    protected function isRunnable(string $binary): bool
    {
        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            return is_file($binary);
        }

        foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $dir) {
            if ($dir === '') {
                continue;
            }

            foreach (['', '.exe', '.cmd', '.bat'] as $extension) {
                if (is_file(rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $binary . $extension)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * A file that does not exist yet — tempnam() would create one, which VACUUM INTO
     * and ZipArchive would both refuse.
     */
    protected function tempPath(): string
    {
        return storage_path('framework/backup-' . bin2hex(random_bytes(8)) . '.gz');
    }

    protected function quoteIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    protected function quoteValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_int($value) => (string) $value,
            is_float($value) => is_finite($value) ? sprintf('%.17G', $value) : 'NULL',
            is_bool($value) => $value ? '1' : '0',
            default => "'" . str_replace("'", "''", (string) $value) . "'",
        };
    }
}
