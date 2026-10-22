<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests write, prune and inspect backups; keep them away from the real ones.
        $backups = storage_path('framework/testing/backups');
        File::deleteDirectory($backups);
        config(['backup.path' => $backups]);
    }
}
