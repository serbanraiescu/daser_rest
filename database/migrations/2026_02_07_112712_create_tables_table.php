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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->integer('seats')->default(4);
            $table->integer('current_pax')->nullable();
            $table->enum('shape', ['round', 'square', 'rectangle'])->default('square');
            $table->integer('x')->default(0);
            $table->integer('y')->default(0);
            $table->integer('width')->default(80);
            $table->integer('height')->default(80);
            $table->integer('rotation')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
