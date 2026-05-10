<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('license_key')->nullable();
            $table->string('fingerprint')->nullable();
            $table->string('status')->default('unverified'); // active, denied, grace_period, unverified
            $table->boolean('is_grace_period')->default(false);
            $table->text('message')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_statuses');
    }
};
