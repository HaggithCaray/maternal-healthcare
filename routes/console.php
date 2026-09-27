<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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
