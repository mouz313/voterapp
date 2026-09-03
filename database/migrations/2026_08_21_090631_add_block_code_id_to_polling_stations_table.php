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
        Schema::table('polling_stations', function (Blueprint $table) {
            $table->foreignId('block_code_id')->nullable()->after('uc_id')->constrained('block_codes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('polling_stations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('block_code_id');
        });
    }
};
