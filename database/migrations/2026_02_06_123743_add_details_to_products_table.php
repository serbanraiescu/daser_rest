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
        Schema::table('products', function (Blueprint $table) {
             if (!Schema::hasColumn('products', 'vat_rate')) {
                $table->string('vat_rate')->nullable(); // Stores the rate value e.g '19'
             }
             if (!Schema::hasColumn('products', 'nutritional_data')) {
                $table->json('nutritional_data')->nullable(); // calories, protein, etc.
             }
             if (!Schema::hasColumn('products', 'allergens')) {
                $table->text('allergens')->nullable(); // comma separated text or simple string
             }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['vat_rate', 'nutritional_data', 'allergens']);
        });
    }
};
