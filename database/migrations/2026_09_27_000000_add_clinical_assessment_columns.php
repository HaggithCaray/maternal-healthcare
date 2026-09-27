<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maternal_checkups', function (Blueprint $table) {
            $table->json('risk_flags')->nullable()->after('status');
        });

        Schema::table('growth_measurements', function (Blueprint $table) {
            // WHO Child Growth Standards z-scores
            $table->decimal('weight_for_age_z', 5, 2)->nullable()->after('head_circumference_cm');
            $table->decimal('height_for_age_z', 5, 2)->nullable()->after('weight_for_age_z');
            $table->decimal('weight_for_height_z', 5, 2)->nullable()->after('height_for_age_z');
        });
    }

    public function down(): void
    {
        Schema::table('maternal_checkups', function (Blueprint $table) {
            $table->dropColumn('risk_flags');
        });

        Schema::table('growth_measurements', function (Blueprint $table) {
            $table->dropColumn(['weight_for_age_z', 'height_for_age_z', 'weight_for_height_z']);
        });
    }
};
