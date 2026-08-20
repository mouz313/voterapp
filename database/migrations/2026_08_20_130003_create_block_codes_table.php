<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uc_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->timestamps();

            $table->unique(['uc_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_codes');
    }
};
