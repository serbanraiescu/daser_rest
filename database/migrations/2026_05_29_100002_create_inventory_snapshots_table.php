<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Inventar Mai 2026"
            $table->date('snapshot_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_snapshot_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_snapshot_id')->constrained('inventory_snapshots')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('system_stock', 12, 3);   // stoc scriptic la momentul snapshot
            $table->decimal('physical_stock', 12, 3)->nullable(); // completat manual
            $table->decimal('difference', 12, 3)->nullable();     // calculat automat
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_snapshot_items');
        Schema::dropIfExists('inventory_snapshots');
    }
};
