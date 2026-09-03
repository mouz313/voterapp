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
            $table->foreignId('national_assembly_id')->nullable()->after('tehsil_id')->constrained('national_assemblies')->nullOnDelete();
            $table->foreignId('provincial_assembly_id')->nullable()->after('national_assembly_id')->constrained('provincial_assemblies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ucs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('national_assembly_id');
            $table->dropConstrainedForeignId('provincial_assembly_id');
        });
    }
};
