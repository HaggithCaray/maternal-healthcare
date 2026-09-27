<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Registration used to store the checkbox map ({"Hypertension": "1"}) while the edit form stored a
 * list (["Hypertension"]). Rewrite every row as a list so both forms and reports read the same shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('maternal_records')->whereNotNull('medical_history')->orderBy('id')->each(function ($row) {
            $history = json_decode($row->medical_history, true);

            if (! is_array($history) || array_is_list($history)) {
                return;
            }

            $conditions = array_keys(array_filter($history, fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)));

            DB::table('maternal_records')->where('id', $row->id)->update([
                'medical_history' => json_encode(array_values($conditions)),
            ]);
        });
    }

    public function down(): void
    {
        // Lists are the canonical format; nothing to restore.
    }
};
