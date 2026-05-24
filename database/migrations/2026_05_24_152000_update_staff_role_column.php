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
        Schema::table('staff_members', function (Blueprint $table) {
            // Altering the role column to a generic string type prevents SQL errors when adding new operational roles like 'service'
            $table->string('role')->default('waiter')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->enum('role', ['waiter', 'kitchen', 'bar', 'manager'])->default('waiter')->change();
        });
    }
};
