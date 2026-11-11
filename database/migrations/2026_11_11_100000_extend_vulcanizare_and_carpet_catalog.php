<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $vulcanizare = $this->category('vulcanizare', 'Vulcanizare');
            $autoturisme = $this->category('vulcanizare-autoturisme', 'Autoturisme', $vulcanizare);
            $spalatorie = $this->category('spalatorie', 'Spălătorie');
            $covoare = $this->category('spalatorie-covoare', 'Covoare', $spalatorie);

            $this->appendItems($autoturisme, 'buc.', [
                'Demontat + montat + echilibrat SUV-4x4' => 50,
                'Curățat jantă' => 10,
                'ANVELOPE SH 50' => 50,
                'ANVELOPE SH 70' => 70,
                'ANVELOPE SH 100' => 100,
            ]);
            $this->appendItems($covoare, 'm²', [
                'Covoare MP 5' => 5,
                'Covoare MP 10' => 10,
                'Covoare MP 50' => 50,
                'Covoare MP 100' => 100,
            ]);
        });
    }

    private function category(string $slug, string $name, ?int $parentId = null): int
    {
        $siblings = DB::table('service_categories')->where('parent_id', $parentId);
        $id = DB::table('service_categories')->where('slug', $slug)->value('id')
            ?? (clone $siblings)->where('name', $name)->orderBy('id')->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        return DB::table('service_categories')->insertGetId([
            'slug' => $slug,
            'name' => $name,
            'parent_id' => $parentId,
            'sort_order' => (int) $siblings->max('sort_order') + 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function appendItems(int $categoryId, string $unit, array $prices): void
    {
        $items = DB::table('service_items')->where('service_category_id', $categoryId);
        $sortOrder = (int) $items->max('sort_order');

        foreach ($prices as $name => $price) {
            if ((clone $items)->where('name', $name)->exists()) {
                continue;
            }

            $sortOrder += 10;
            DB::table('service_items')->insert([
                'service_category_id' => $categoryId,
                'name' => $name,
                'price' => $price,
                'unit' => $unit,
                'sort_order' => $sortOrder,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Pozițiile pot fi deja editate sau folosite pe fișe. Păstrăm catalogul operațional la rollback.
    }
};
