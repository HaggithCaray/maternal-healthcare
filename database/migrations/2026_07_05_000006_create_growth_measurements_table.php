<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growth_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_record_id')->constrained('child_records')->onDelete('cascade');
            $table->date('date');
            $table->integer('age_months');
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('height_cm', 5, 2);
            $table->decimal('head_circumference_cm', 5, 2)->nullable();
            $table->string('status')->default('Normal'); // 'Normal', 'Underweight', 'Overweight', etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_measurements');
    }
};
