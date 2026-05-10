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
            //
            $table->decimal('measurement_value', 10, 2)->nullable()->default(null);
            $table->string('measurement_unit')->nullable()->default(null); // g, ml, kg, l, buc, portie
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['measurement_value', 'measurement_unit']);
        });
    }
};
