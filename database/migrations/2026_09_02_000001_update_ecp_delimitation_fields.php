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
        Schema::table('ucs', function (Blueprint $table) {
            $table->string('uc_no')->nullable()->after('tehsil_id'); // e.g. 1, 2, 3... scoped to tehsil
            $table->string('name_ur')->nullable()->after('name'); // Urdu name of UC / Revenue Estate
        });

        Schema::table('block_codes', function (Blueprint $table) {
            $table->text('area_name')->nullable()->after('code'); // Extent of UC / Electoral Area (English)
            $table->text('area_name_ur')->nullable()->after('area_name'); // Extent of UC (Urdu: بند روڈ شادی پورہ...)
            $table->integer('population')->nullable()->after('area_name_ur'); // Census Population
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ucs', function (Blueprint $table) {
            $table->dropColumn(['uc_no', 'name_ur']);
        });

        Schema::table('block_codes', function (Blueprint $table) {
            $table->dropColumn(['area_name', 'area_name_ur', 'population']);
        });
    }
};
