<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'locale']);
        });

        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('name');
            $table->timestamps();
            $table->unique(['category_id', 'locale']);
        });

        $now = now();

        DB::table('products')->orderBy('id')->chunkById(200, function ($products) use ($now): void {
            $rows = [];
            foreach ($products as $product) {
                $rows[] = [
                    'product_id' => $product->id,
                    'locale' => 'ro',
                    'name' => $product->name,
                    'description' => $product->description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (!empty($product->name_en)) {
                    $rows[] = [
                        'product_id' => $product->id,
                        'locale' => 'en',
                        'name' => $product->name_en,
                        'description' => $product->description_en,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            if ($rows !== []) {
                DB::table('product_translations')->insert($rows);
            }
        });

        DB::table('categories')->orderBy('id')->chunkById(200, function ($categories) use ($now): void {
            $rows = [];
            foreach ($categories as $category) {
                $rows[] = [
                    'category_id' => $category->id,
                    'locale' => 'ro',
                    'name' => $category->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows !== []) {
                DB::table('category_translations')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_translations');
        Schema::dropIfExists('product_translations');
    }
};
