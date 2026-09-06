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
        Schema::table('search_logs', function (Blueprint $table) {
            $table->unsignedInteger('cnic_count')->default(0)->after('results_count');
            $table->unsignedInteger('name_count')->default(0)->after('cnic_count');
            $table->unsignedInteger('gharana_count')->default(0)->after('name_count');
            $table->unsignedInteger('silsala_count')->default(0)->after('gharana_count');

            $table->unique(['user_id', 'device_uid'], 'search_logs_user_device_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropUnique('search_logs_user_device_unique');
            $table->dropColumn(['cnic_count', 'name_count', 'gharana_count', 'silsala_count']);
        });
    }
};
