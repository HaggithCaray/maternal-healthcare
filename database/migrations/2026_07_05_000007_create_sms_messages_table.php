<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->string('phone_number');
            $table->text('message');
            $table->string('status')->default('Pending'); // 'Sent', 'Failed', 'Pending'
            $table->timestamp('sent_at')->nullable();
            $table->string('type')->default('Reminder'); // 'Reminder', 'Alert', 'Manual'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
