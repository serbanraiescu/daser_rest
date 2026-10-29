<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('service_categories')
                ->nullOnDelete();
        });

        Schema::table('service_order_items', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('service_order_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
