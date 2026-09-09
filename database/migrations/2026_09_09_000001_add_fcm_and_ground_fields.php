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
        // 1. Add fcm_token to candidate_devices
        if (Schema::hasTable('candidate_devices') && !Schema::hasColumn('candidate_devices', 'fcm_token')) {
            Schema::table('candidate_devices', function (Blueprint $table) {
                $table->text('fcm_token')->nullable()->after('api_token');
            });
        }

        // 2. Add fcm_token to campaign_workers
        if (Schema::hasTable('campaign_workers') && !Schema::hasColumn('campaign_workers', 'fcm_token')) {
            Schema::table('campaign_workers', function (Blueprint $table) {
                $table->text('fcm_token')->nullable()->after('api_token');
            });
        }

        // 3. Add influencer_phone, latitude, longitude to gharana_surveys
        if (Schema::hasTable('gharana_surveys')) {
            Schema::table('gharana_surveys', function (Blueprint $table) {
                if (!Schema::hasColumn('gharana_surveys', 'influencer_phone')) {
                    $table->string('influencer_phone', 30)->nullable()->after('influencer_name');
                }
                if (!Schema::hasColumn('gharana_surveys', 'latitude')) {
                    $table->decimal('latitude', 10, 8)->nullable()->after('parchi_issued_at');
                }
                if (!Schema::hasColumn('gharana_surveys', 'longitude')) {
                    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('candidate_devices') && Schema::hasColumn('candidate_devices', 'fcm_token')) {
            Schema::table('candidate_devices', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        if (Schema::hasTable('campaign_workers') && Schema::hasColumn('campaign_workers', 'fcm_token')) {
            Schema::table('campaign_workers', function (Blueprint $table) {
                $table->dropColumn('fcm_token');
            });
        }

        if (Schema::hasTable('gharana_surveys')) {
            Schema::table('gharana_surveys', function (Blueprint $table) {
                if (Schema::hasColumn('gharana_surveys', 'influencer_phone')) {
                    $table->dropColumn('influencer_phone');
                }
                if (Schema::hasColumn('gharana_surveys', 'latitude')) {
                    $table->dropColumn('latitude');
                }
                if (Schema::hasColumn('gharana_surveys', 'longitude')) {
                    $table->dropColumn('longitude');
                }
            });
        }
    }
};
