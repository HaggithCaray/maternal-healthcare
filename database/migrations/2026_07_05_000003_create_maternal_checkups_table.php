<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maternal_checkups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maternal_record_id')->constrained('maternal_records')->onDelete('cascade');
            $table->integer('visit_number');
            $table->date('date');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('bp')->nullable(); // Blood pressure (e.g. "120/80")
            $table->string('age_of_gestation')->nullable(); // e.g. "24w 2d"
            $table->integer('fetal_heart_rate')->nullable(); // in bpm
            $table->string('attendant')->nullable(); // doctor/midwife name
            $table->string('status')->default('Healthy'); // 'Healthy', 'Screening', 'At Risk'
            $table->text('notes')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maternal_checkups');
    }
};
