<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postpartum_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maternal_record_id')->constrained('maternal_records')->onDelete('cascade');
            $table->date('date');
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('bp')->nullable(); // Blood pressure (e.g. "120/80")
            $table->integer('days_postpartum')->nullable(); // Derived from the delivery date on save
            $table->string('attendant')->nullable(); // doctor/midwife name
            $table->string('status')->default('Healthy'); // 'Healthy', 'Monitor', 'High Risk'
            $table->json('risk_flags')->nullable();
            $table->text('notes')->nullable();
            $table->date('next_visit_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postpartum_visits');
    }
};
