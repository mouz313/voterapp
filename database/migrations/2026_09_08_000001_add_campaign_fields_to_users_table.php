<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('candidate_code', 64)->nullable()->unique()->after('email');
            $table->string('party_slogan', 255)->nullable()->after('candidate_symbol');
            $table->string('leader_image', 255)->nullable()->after('candidate_image');
        });

        // Generate unique candidate_code for existing candidates
        $candidates = User::where('role', 'candidate')->get();
        foreach ($candidates as $cand) {
            $base = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $cand->name));
            if (empty($base)) {
                $base = 'CAND';
            }
            $base = substr($base, 0, 8);
            $cand->update([
                'candidate_code' => $base . '-' . $cand->id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['candidate_code', 'party_slogan', 'leader_image']);
        });
    }
};
