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
            $table->string('role')->default('candidate')->after('email'); // admin, candidate
            $table->foreignId('uc_id')->nullable()->after('role')->constrained('ucs')->nullOnDelete();
            $table->string('phone')->nullable()->after('uc_id');
            $table->integer('max_devices')->default(20)->after('phone');
            $table->string('status')->default('active')->after('max_devices'); // active, suspended
            $table->timestamp('expires_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uc_id');
            $table->dropColumn(['role', 'phone', 'max_devices', 'status', 'expires_at']);
        });
    }
};
