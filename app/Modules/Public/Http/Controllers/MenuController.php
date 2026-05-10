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
        
        $menus = Menu::query()
            ->where('is_active', true)
            ->with(['categories' => function ($query) {
                $query->where('is_active', true)
                      ->with(['products' => function ($q) {
                          $q->where('is_active', true)
                            ->with(['variations', 'ingredients', 'allergenRelations']);
                      }])
                      ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return view('public.menu.index', compact('settings', 'menus'));
    }
}
