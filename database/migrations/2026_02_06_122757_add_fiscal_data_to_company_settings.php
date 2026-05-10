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
        Schema::table('company_settings', function (Blueprint $table) {
            $table->json('vat_rates')->nullable();
            $table->string('fiscal_code')->nullable();
            $table->string('trade_register')->nullable();
            $table->string('fiscal_address')->nullable();
            $table->text('spv_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn(['vat_rates', 'fiscal_code', 'trade_register', 'fiscal_address', 'spv_token']);
        });
    }
};
