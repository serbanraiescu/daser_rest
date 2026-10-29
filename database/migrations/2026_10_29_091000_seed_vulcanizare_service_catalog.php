<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $roots = [
            ['name' => 'Vulcanizare', 'slug' => 'vulcanizare', 'sort_order' => 10],
            ['name' => 'Spălătorie', 'slug' => 'spalatorie', 'sort_order' => 20],
        ];

        foreach ($roots as $root) {
            DB::table('service_categories')->updateOrInsert(
                ['slug' => $root['slug']],
                [
                    'parent_id' => null,
                    'name' => $root['name'],
                    'description' => null,
                    'sort_order' => $root['sort_order'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $rootIds = DB::table('service_categories')
            ->whereIn('slug', ['vulcanizare', 'spalatorie'])
            ->pluck('id', 'slug');

        $subcategories = [
            ['parent' => 'vulcanizare', 'name' => 'Autoturisme', 'slug' => 'vulcanizare-autoturisme', 'sort_order' => 10],
            ['parent' => 'vulcanizare', 'name' => 'Camioane', 'slug' => 'vulcanizare-camioane', 'sort_order' => 20],
            ['parent' => 'vulcanizare', 'name' => 'Petice', 'slug' => 'vulcanizare-petice', 'sort_order' => 30],
            ['parent' => 'vulcanizare', 'name' => 'Extra auto', 'slug' => 'vulcanizare-extra-auto', 'sort_order' => 40],
            ['parent' => 'spalatorie', 'name' => 'Spălare interior / exterior', 'slug' => 'spalare-interior-exterior', 'sort_order' => 10],
        ];

        foreach ($subcategories as $subcategory) {
            DB::table('service_categories')->updateOrInsert(
                ['slug' => $subcategory['slug']],
                [
                    'parent_id' => $rootIds[$subcategory['parent']],
                    'name' => $subcategory['name'],
                    'description' => null,
                    'sort_order' => $subcategory['sort_order'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $categoryIds = DB::table('service_categories')
            ->whereIn('slug', array_column($subcategories, 'slug'))
            ->pluck('id', 'slug');

        $catalog = [
            'vulcanizare-autoturisme' => [
                ['Demontat + montat + echilibrat R13–R15', 35, 'buc.'],
                ['Demontat + montat + echilibrat R16–R17', 40, 'buc.'],
                ['Demontat + montat + echilibrat R18–R20', 45, 'buc.'],
                ['Demontat + montat + echilibrat R21–R24', 50, 'buc.'],
                ['Dejantat sau montat R13–R18', 10, 'buc.'],
                ['Echilibrat R13–R18', 15, 'buc.'],
                ['Dejantat sau montat R19–R24', 15, 'buc.'],
                ['Echilibrat R19–R24', 20, 'buc.'],
            ],
            'vulcanizare-camioane' => [
                ['Dat jos + dejantat + montat', 100, 'buc.'],
                ['Dejantat', 20, 'buc.'],
                ['Desfăcut + montat rezervă', 30, 'buc.'],
                ['Doar dejantat și jantat', 50, 'buc.'],
                ['Valvă îndoită', 30, 'buc.'],
                ['Valvă prelungire', 30, 'buc.'],
                ['Timp extra – 1 demontare', 20, 'serviciu'],
                ['Timp extra – 2 demontări', 40, 'serviciu'],
                ['Presiune roată camion', 8, 'buc.'],
            ],
            'vulcanizare-petice' => [
                ['Petic nr. 1', 10, 'buc.'],
                ['Petic nr. 2', 15, 'buc.'],
                ['Petic nr. 3', 20, 'buc.'],
                ['Petic dimensiunea 110', 35, 'buc.'],
                ['Petic dimensiunea 112', 50, 'buc.'],
                ['Petic dimensiunea 120', 80, 'buc.'],
                ['Petic dimensiunea 125', 100, 'buc.'],
                ['Petic dimensiunea 135', 120, 'buc.'],
                ['Extra petec', 10, 'buc.'],
            ],
            'vulcanizare-extra-auto' => [
                ['Șnur pe mașină', 20, 'buc.'],
                ['Șnur plus demontat', 30, 'buc.'],
                ['Presiune roți', 2.5, 'buc.'],
                ['Valvă cauciuc', 10, 'buc.'],
                ['Ventil', 5, 'buc.'],
                ['Extra', 10, 'serviciu'],
            ],
            'spalare-interior-exterior' => [
                ['Interior + exterior autoturism', 80, 'serviciu'],
                ['Interior + exterior SUV', 100, 'serviciu'],
                ['Exterior', 40, 'serviciu'],
                ['Interior', 40, 'serviciu'],
                ['Extra murdar', 10, 'serviciu'],
                ['Extra foarte murdar', 20, 'serviciu'],
            ],
        ];

        foreach ($catalog as $categorySlug => $items) {
            foreach ($items as $index => [$name, $price, $unit]) {
                DB::table('service_items')->updateOrInsert(
                    [
                        'service_category_id' => $categoryIds[$categorySlug],
                        'name' => $name,
                    ],
                    [
                        'description' => null,
                        'price' => $price,
                        'unit' => $unit,
                        'sort_order' => ($index + 1) * 10,
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Catalogul poate fi editat de client după instalare; rollback-ul nu șterge date operaționale.
    }
};
