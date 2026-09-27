<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per offline item already applied, so a retried or concurrent sync can't create it twice.
     */
    public function up(): void
    {
        Schema::create('sync_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique(); // client UUID, or a hash of the item for older clients
            $table->string('type', 50);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_receipts');
    }
};
