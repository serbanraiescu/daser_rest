<?php

namespace Database\Seeders;

use App\Modules\Menu\Models\Allergen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AllergenSeeder extends Seeder
{
    public function run(): void
    {
        $allergens = [
            'Gluten',
            'Lactate',
            'Ou',
            'Pește',
            'Țelină',
            'Nuci',
            'Fructe de mare',
            'Muștar',
            'Soia',
            'Susan',
            'Arahide',
            'Lupin',
            'Sulfiți',
            'Moluște',
        ];

        foreach ($allergens as $name) {
            Allergen::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );
        }
    }
}
