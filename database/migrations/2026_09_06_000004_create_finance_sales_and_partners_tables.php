<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Selling Parties / Distribution Channels (Party A, Party B, etc.)
        Schema::create('sales_parties', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Party A", "Party B"
            $table->string('code', 50)->unique(); // e.g. "PARTY_A", "PARTY_B"
            $table->string('contact_person')->nullable();
            $table->string('phone', 50)->nullable();
            $table->decimal('default_price', 12, 2)->default(20000.00);
            $table->decimal('commission_rate_pct', 5, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Candidate App Sales Ledger
        Schema::create('candidate_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sales_party_id')->nullable()->constrained('sales_parties')->onDelete('set null');
            $table->decimal('sale_amount', 12, 2)->default(20000.00);
            $table->decimal('amount_paid', 12, 2)->default(20000.00);
            $table->enum('payment_status', ['paid', 'pending', 'partial'])->default('paid');
            $table->string('payment_method', 50)->default('Cash'); // Cash, Bank Transfer, JazzCash, EasyPaisa, etc.
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sales_party_id', 'payment_status']);
            $table->index('payment_date');
        });

        // 3. Partners & Investors
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['investor', 'partner'])->default('partner');
            $table->string('phone', 50)->nullable();
            $table->decimal('invested_capital', 14, 2)->default(0.00); // Initial money invested to be returned
            $table->decimal('profit_share_pct', 5, 2)->default(0.00); // Profit percentage, e.g. 35.00%
            $table->text('bank_details')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Partner Payouts & Capital Returns
        Schema::create('partner_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners')->onDelete('cascade');
            $table->decimal('amount', 14, 2);
            $table->enum('payout_type', ['capital_return', 'profit_distribution'])->default('profit_distribution');
            $table->date('payout_date');
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['partner_id', 'payout_type']);
        });

        // 5. Finance Settings & Security Password Vault
        Schema::create('finance_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed initial Party A, Party B, and Default Finance Security Password ('voter123')
        $now = now();

        DB::table('sales_parties')->insert([
            [
                'name' => 'Party A',
                'code' => 'PARTY_A',
                'contact_person' => 'Party A Lead',
                'phone' => '',
                'default_price' => 20000.00,
                'commission_rate_pct' => 0.00,
                'is_active' => true,
                'notes' => 'Primary Sales Channel A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Party B',
                'code' => 'PARTY_B',
                'contact_person' => 'Party B Lead',
                'phone' => '',
                'default_price' => 20000.00,
                'commission_rate_pct' => 0.00,
                'is_active' => true,
                'notes' => 'Secondary Sales Channel B',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('finance_settings')->insert([
            [
                'key' => 'finance_password_hash',
                'value' => Hash::make('voter123'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'session_timeout_minutes',
                'value' => '60',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'currency_symbol',
                'value' => 'PKR',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_settings');
        Schema::dropIfExists('partner_payouts');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('candidate_sales');
        Schema::dropIfExists('sales_parties');
    }
};
