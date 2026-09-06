<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update polling_stations table: Add standalone index for uc_id foreign key first
        try {
            Schema::table('polling_stations', function (Blueprint $table) {
                $table->index('uc_id', 'polling_stations_uc_id_fk_idx');
            });
        } catch (\Throwable $e) {
            // ignore if already exists
        }

        try {
            Schema::table('polling_stations', function (Blueprint $table) {
                $table->dropUnique('polling_stations_uc_id_name_unique');
            });
        } catch (\Throwable $e) {
            // Ignore if constraint name differs or already dropped
        }

        Schema::table('polling_stations', function (Blueprint $table) {
            $table->string('station_no', 50)->nullable()->after('uc_id');
            $table->enum('gender', ['male', 'female', 'combined'])->default('combined')->after('name');
            $table->unsignedSmallInteger('male_booths')->nullable()->after('address');
            $table->unsignedSmallInteger('female_booths')->nullable()->after('male_booths');
            $table->unsignedSmallInteger('total_booths')->nullable()->after('female_booths');

            $table->index(['uc_id', 'gender']);
        });

        // 2. Update block_codes table to store designated Male and Female polling stations
        Schema::table('block_codes', function (Blueprint $table) {
            $table->foreignId('male_polling_station_id')
                ->nullable()
                ->after('population')
                ->constrained('polling_stations')
                ->nullOnDelete();

            $table->foreignId('female_polling_station_id')
                ->nullable()
                ->after('male_polling_station_id')
                ->constrained('polling_stations')
                ->nullOnDelete();
        });

        // 3. Migrate existing polling_stations block_code_id into block_codes if present
        $existingStations = DB::table('polling_stations')->whereNotNull('block_code_id')->get();
        foreach ($existingStations as $st) {
            DB::table('block_codes')
                ->where('id', $st->block_code_id)
                ->update([
                    'male_polling_station_id' => $st->id,
                    'female_polling_station_id' => $st->id,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('block_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('male_polling_station_id');
            $table->dropConstrainedForeignId('female_polling_station_id');
        });

        Schema::table('polling_stations', function (Blueprint $table) {
            $table->dropIndex(['uc_id', 'gender']);
            $table->dropColumn(['station_no', 'gender', 'male_booths', 'female_booths', 'total_booths']);
            $table->unique(['uc_id', 'name']);
        });
    }
};
