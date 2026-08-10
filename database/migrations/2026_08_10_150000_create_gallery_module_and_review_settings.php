<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->date('event_date')->nullable();
            $table->boolean('show_on_homepage')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_album_id')->constrained('gallery_albums')->cascadeOnDelete();
            $table->string('image');
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('company_settings', function (Blueprint $table) {
            $table->boolean('show_google_reviews')->default(false);
            $table->string('google_reviews_title')->nullable();
            $table->decimal('google_rating', 2, 1)->nullable();
            $table->unsignedInteger('google_review_count')->nullable();
            $table->text('google_reviews_url')->nullable();
            $table->text('google_review_form_url')->nullable();
        });

        $settings = DB::table('company_settings')->first();
        $legacyGallery = $settings?->gallery_content;
        $images = is_string($legacyGallery) ? json_decode($legacyGallery, true) : $legacyGallery;

        if (is_array($images) && $images !== []) {
            $albumId = DB::table('gallery_albums')->insertGetId([
                'title' => 'Galerie generală',
                'slug' => 'galerie-generala',
                'description' => 'Fotografii migrate din galeria existentă.',
                'show_on_homepage' => true,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (array_values($images) as $index => $image) {
                if (!is_string($image) || trim($image) === '') {
                    continue;
                }

                DB::table('gallery_images')->insert([
                    'gallery_album_id' => $albumId,
                    'image' => $image,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_google_reviews',
                'google_reviews_title',
                'google_rating',
                'google_review_count',
                'google_reviews_url',
                'google_review_form_url',
            ]);
        });

        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('gallery_albums');
    }
};
