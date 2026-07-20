<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maternal_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->date('lmp')->nullable();
            $table->date('edd')->nullable();
            $table->integer('gravida')->default(1);
            $table->integer('para')->default(0);
            $table->integer('abortions')->default(0);
            $table->integer('still_births')->default(0);
            $table->string('philhealth_number')->nullable();
            $table->string('blood_type')->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->text('allergies')->nullable();
            $table->json('medical_history')->nullable(); // Checklist of conditions (Hypertension, Diabetes, etc.)
            $table->json('birth_plan')->nullable(); // Planning details (attendant, hospital, transport, emergency contacts)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maternal_records');
    }
};
