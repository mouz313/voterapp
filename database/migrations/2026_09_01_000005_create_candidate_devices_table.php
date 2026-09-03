<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidate_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('device_uid'); // Unique Device ID / UUID
            $table->string('device_name')->nullable(); // e.g. Samsung Galaxy S23, iPhone 15
            $table->string('platform')->nullable(); // android, ios
            $table->string('app_version')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'device_uid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_devices');
    }
};
