<?php

use App\Models\GrowthMeasurement;
use App\Models\MaternalCheckup;
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
