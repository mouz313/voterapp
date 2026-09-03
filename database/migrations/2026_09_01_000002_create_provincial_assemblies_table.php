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
        Schema::create('provincial_assemblies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('national_assembly_id')->nullable()->constrained('national_assemblies')->nullOnDelete();
            $table->string('code')->unique(); // e.g. PP-150, PS-10, PK-45, PB-12
            $table->string('name'); // e.g. Lahore-VII
            $table->string('province')->default('Punjab'); // Punjab (PP), Sindh (PS), Khyber Pakhtunkhwa (PK), Balochistan (PB)
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provincial_assemblies');
    }
};
