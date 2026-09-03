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
        Schema::table('users', function (Blueprint $table) {
            $table->string('party_name')->nullable()->after('phone');
            $table->boolean('is_independent')->default(false)->after('party_name');
            $table->string('candidate_symbol')->nullable()->after('is_independent');
            $table->string('party_logo')->nullable()->after('candidate_symbol');
            $table->string('candidate_image')->nullable()->after('party_logo');
            $table->string('candidate_symbol_image')->nullable()->after('candidate_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'party_name',
                'is_independent',
                'candidate_symbol',
                'party_logo',
                'candidate_image',
                'candidate_symbol_image',
            ]);
        });
    }
};
