<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->text('description_en')->nullable()->after('description');
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->boolean('enable_allergens')->default(true)->after('show_all_products_initially');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('enable_allergens');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'description_en']);
        });
    }
};
