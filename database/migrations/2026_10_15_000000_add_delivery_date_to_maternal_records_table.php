<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maternal_records', function (Blueprint $table) {
            // When set, postpartum (postnatal) care tracking becomes available for this mother.
            $table->date('delivery_date')->nullable()->after('edd');
        });
    }

    public function down(): void
    {
        Schema::table('maternal_records', function (Blueprint $table) {
            $table->dropColumn('delivery_date');
        });
    }
};
