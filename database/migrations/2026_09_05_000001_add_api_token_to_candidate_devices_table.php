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
        Schema::table('candidate_devices', function (Blueprint $table) {
            $table->string('api_token', 80)->nullable()->unique()->after('device_uid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_devices', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
};
