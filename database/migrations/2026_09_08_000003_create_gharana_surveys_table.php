<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gharana_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->string('block_code', 50)->index();
            $table->unsignedInteger('gharana_no')->index();
            $table->enum('sentiment', ['pakka', 'kacha', 'mukhalif', 'unassigned'])->default('unassigned');
            $table->string('influencer_name', 150)->nullable();
            $table->unsignedInteger('voter_count')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_vip_visit_requested')->default(false);
            $table->foreignId('visited_by_worker_id')->nullable()->constrained('campaign_workers')->onDelete('set null');
            $table->timestamp('visited_at')->nullable();
            $table->timestamp('parchi_issued_at')->nullable();
            $table->timestamps();

            $table->unique(['candidate_id', 'block_code', 'gharana_no'], 'cand_block_gharana_unique');
            $table->index(['candidate_id', 'sentiment']);
            $table->index(['candidate_id', 'is_vip_visit_requested']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gharana_surveys');
    }
};
