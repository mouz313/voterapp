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
        Schema::create('national_assemblies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. NA-120
            $table->string('name'); // e.g. Lahore-IV
            $table->string('province')->default('Punjab'); // Punjab, Sindh, Khyber Pakhtunkhwa, Balochistan, Islamabad
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('national_assemblies');
    }
};
