<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->boolean('track_stock')->default(false)->after('is_active');
            $table->foreignId('inventory_item_id')
                ->nullable()
                ->after('track_stock')
                ->constrained('inventory_items')
                ->nullOnDelete();
            $table->decimal('stock_quantity_per_unit', 12, 3)
                ->nullable()
                ->after('inventory_item_id')
                ->comment('Cantitatea scăzută din inventar per unitate din rețetă (ex: 0.200 pentru 200g)');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
            $table->dropColumn(['track_stock', 'inventory_item_id', 'stock_quantity_per_unit']);
        });
    }
};
