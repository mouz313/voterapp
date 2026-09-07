<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 150);
            $table->string('phone', 50)->nullable();
            $table->string('pin', 10); // 4-digit PIN e.g. 1234
            $table->string('assigned_block_code', 50)->index();
            $table->string('device_uid', 100)->nullable();
            $table->string('api_token', 80)->nullable()->unique();
            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['candidate_id', 'is_active']);
            $table->index(['candidate_id', 'assigned_block_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_workers');
    }
};
