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
        Schema::table('company_settings', function (Blueprint $table) {
            // Frontend Design
            $table->json('frontend_colors')->nullable()->after('default_language');
            
            // Cookie Consent
            $table->json('cookie_consent')->nullable()->after('frontend_colors');
            
            // Page Contents
            $table->longText('about_content')->nullable()->after('cookie_consent');
            $table->longText('terms_content')->nullable()->after('about_content');
            $table->longText('gdpr_content')->nullable()->after('terms_content');
            $table->longText('privacy_content')->nullable()->after('gdpr_content');
            $table->json('gallery_content')->nullable()->after('privacy_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'frontend_colors',
                'cookie_consent',
                'about_content',
                'terms_content',
                'gdpr_content',
                'privacy_content',
                'gallery_content'
            ]);
        });
    }
};
