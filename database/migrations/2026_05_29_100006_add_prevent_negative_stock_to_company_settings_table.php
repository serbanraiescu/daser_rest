<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->boolean('prevent_negative_stock')
                ->default(false)
                ->after('enable_service_module')
                ->comment('Dacă true, nu permite scăderea stocului sub 0');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn('prevent_negative_stock');
        });
    }
};
