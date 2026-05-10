<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Direct SQL to ensure it runs even if Schema builder is confused
        if (!Schema::hasColumn('categories', 'menu_id')) {
             Schema::table('categories', function (Blueprint $table) {
                $table->foreignId('menu_id')->nullable()->constrained()->nullOnDelete()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('categories', 'menu_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropForeign(['menu_id']);
                $table->dropColumn('menu_id');
            });
        }
    }
};
