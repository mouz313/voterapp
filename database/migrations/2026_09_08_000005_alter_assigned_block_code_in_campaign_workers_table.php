<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_workers', function (Blueprint $table) {
            $table->string('assigned_block_code', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('campaign_workers', function (Blueprint $table) {
            $table->string('assigned_block_code', 50)->change();
        });
    }
};

