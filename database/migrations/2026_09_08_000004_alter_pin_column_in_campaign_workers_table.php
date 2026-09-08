<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_workers', function (Blueprint $table) {
            $table->string('pin', 255)->change();
        });

        // Rehash existing plain-text PINs
        $workers = DB::table('campaign_workers')->get();
        foreach ($workers as $worker) {
            if (!empty($worker->pin) && Hash::needsRehash($worker->pin)) {
                DB::table('campaign_workers')
                    ->where('id', $worker->id)
                    ->update(['pin' => Hash::make($worker->pin)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('campaign_workers', function (Blueprint $table) {
            $table->string('pin', 10)->change();
        });
    }
};

