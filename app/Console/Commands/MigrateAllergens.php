<?php

namespace App\Console\Commands;

use App\Modules\Menu\Models\Allergen;
use App\Modules\Menu\Models\Product;
use Illuminate\Console\Command;

class MigrateAllergens extends Command
{
    protected $signature = 'migrate:allergens';
    protected $description = 'Migrate text-based allergens from products table to the new structured relationship';

    public function handle()
    {
        $products = Product::whereNotNull('allergens')->get();
        $allAllergens = Allergen::all();
        
        $this->info('Starting allergen migration for ' . $products->count() . ' products...');
        
        $matchedCount = 0;
        $unmatched = [];

        foreach ($products as $product) {
            $legacyText = $product->getRawOriginal('allergens');
            if (empty($legacyText)) continue;

            $items = array_map('trim', explode(',', $legacyText));
            $foundIds = [];

            foreach ($items as $item) {
                // Try exact match
                $allergen = $allAllergens->first(fn($a) => 
                    mb_strtolower($a->name) === mb_strtolower($item) || 
                    mb_strtolower($a->slug) === mb_strtolower($item)
                );

                if ($allergen) {
                    $foundIds[] = $allergen->id;
                } else {
                    $unmatched[] = "Product #{$product->id} ({$product->name}): '{$item}'";
                }
            }

            if (!empty($foundIds)) {
                $product->allergens()->syncWithoutDetaching($foundIds);
                $matchedCount++;
            }
        }

        $this->info("Migration completed!");
        $this->info("Products updated: {$matchedCount}");
        
        if (!empty($unmatched)) {
            $this->warn("Unmatched allergens found (" . count($unmatched) . "):");
            foreach (array_unique($unmatched) as $error) {
                $this->line(" - " . $error);
            }
        }
    }
}
