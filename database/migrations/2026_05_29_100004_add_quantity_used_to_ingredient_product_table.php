<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_product', function (Blueprint $table) {
            $table->decimal('quantity_used', 12, 3)
                ->default(1)
                ->after('ingredient_id')
                ->comment('Cantitatea din acest ingredient folosită per o porție de produs');
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_product', function (Blueprint $table) {
            $table->dropColumn('quantity_used');
        });
    }
};
