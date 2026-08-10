<?php

namespace App\Modules\Public\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\CompanySetting;
use App\Modules\Gallery\Models\GalleryAlbum;
use Illuminate\View\View;

class LandingOneController extends Controller
{
    public function index(): View
    {
        $settings = CompanySetting::first() ?? new CompanySetting();
        $featuredAlbums = GalleryAlbum::query()
            ->where('is_active', true)
            ->where('show_on_homepage', true)
            ->with('images')
            ->orderBy('sort_order')
            ->orderByDesc('event_date')
            ->limit(3)
            ->get();

        return view('public.landing.theme-one', compact('settings', 'featuredAlbums'));
    }
}
