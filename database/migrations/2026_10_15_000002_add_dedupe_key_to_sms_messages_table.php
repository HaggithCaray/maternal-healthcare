<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            // One automated reminder per thing it is about (vaccine:{id}, nextvisit:{id},
            // postpartum:{id}), so a daily run never messages twice about the same visit.
            // Nullable because manual sends and older rows have no key; MySQL allows many NULLs.
            $table->string('dedupe_key', 100)->nullable()->unique()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('sms_messages', function (Blueprint $table) {
            $table->dropUnique(['dedupe_key']);
            $table->dropColumn('dedupe_key');
        });
    }
};
