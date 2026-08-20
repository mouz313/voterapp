<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ucs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tehsil_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['tehsil_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ucs');
    }
};
