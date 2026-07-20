<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('immunizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_record_id')->constrained('child_records')->onDelete('cascade');
            $table->string('vaccine_name'); // e.g. BCG, HepB, Pentavalent, OPV, IPV, PCV, MMR
            $table->integer('dose_number')->default(1);
            $table->date('scheduled_date');
            $table->date('given_date')->nullable();
            $table->string('administered_by')->nullable();
            $table->string('remarks')->nullable();
            $table->string('status')->default('Scheduled'); // 'Scheduled', 'Given', 'Overdue'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('immunizations');
    }
};
