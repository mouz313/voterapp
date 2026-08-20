<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uc_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_code_id')->constrained()->cascadeOnDelete();
            $table->foreignId('polling_station_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('father_name');
            $table->string('cnic')->unique();
            $table->string('silsala_no')->nullable();
            $table->string('gharana_no')->nullable();
            $table->timestamps();

            $table->index('cnic');
            $table->index('gharana_no');
            $table->index('uc_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voters');
    }
};
