<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name')->default('Daser Restaurant');
            $table->string('company_logo')->nullable();
            
            // Hero Section
            $table->string('hero_title')->default('Welcome to Our Restaurant');
            $table->text('hero_description')->nullable();
            $table->string('hero_background_image')->nullable();
            
            // Contact
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            
            // JSON Data
            $table->json('social_links')->nullable();
            $table->json('opening_hours')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
