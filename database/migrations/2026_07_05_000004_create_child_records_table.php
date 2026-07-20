<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('mother_id')->nullable()->constrained('patients')->onDelete('set null');
            $table->decimal('birth_weight_kg', 5, 2)->nullable();
            $table->decimal('birth_height_cm', 5, 2)->nullable();
            $table->decimal('head_circumference_cm', 5, 2)->nullable();
            $table->string('birth_type')->default('Single'); // 'Single', 'Twins', 'Multiple'
            $table->string('delivery_type')->default('Normal'); // 'Normal', 'Caesarean'
            $table->string('delivery_place')->nullable();
            $table->string('attendant')->nullable();
            $table->string('birth_order')->nullable(); // e.g. "1st", "2nd" child
            $table->string('blood_type')->nullable();
            $table->boolean('has_newborn_screening')->default(false);
            $table->boolean('has_hearing_screening')->default(false);
            $table->boolean('has_eye_prophylaxis')->default(false);
            $table->boolean('has_vitamin_k')->default(false);
            $table->boolean('has_bcg_at_birth')->default(false);
            $table->boolean('has_hepb_at_birth')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_records');
    }
};
