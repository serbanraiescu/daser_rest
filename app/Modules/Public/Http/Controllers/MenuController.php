<?php

namespace App\Modules\Public\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Menu\Models\Category;
use App\Modules\Menu\Models\Menu;
use App\Modules\Settings\Models\CompanySetting;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        $settings = CompanySetting::first() ?? new CompanySetting();
        $showAllergens = $settings->enable_allergens ?? true;
        
        $menus = Menu::query()
            ->where('is_active', true)
            ->with(['categories' => function ($query) use ($showAllergens) {
                $query->where('is_active', true)
                      ->with('translations')
                      ->with(['products' => function ($q) use ($showAllergens) {
                          $relations = ['variations', 'ingredients', 'translations'];
                          if ($showAllergens) {
                              $relations[] = 'allergenRelations';
                          }
                          $q->where('is_active', true)->with($relations);
                      }])
                      ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('public.menu.index', compact('settings', 'menus'));
    }
}
