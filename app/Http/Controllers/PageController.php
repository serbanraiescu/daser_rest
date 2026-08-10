<?php

namespace App\Http\Controllers;

use App\Modules\Settings\Models\CompanySetting;
use App\Modules\Gallery\Models\GalleryAlbum;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function about()
    {
        $settings = CompanySetting::first();
        return view('public.pages.static', [
            'title' => 'Despre Noi',
            'content' => $settings->about_content,
            'settings' => $settings
        ]);
    }

    public function terms()
    {
        $settings = CompanySetting::first();
        return view('public.pages.static', [
            'title' => 'Termeni și Condiții',
            'content' => $settings->terms_content,
            'settings' => $settings
        ]);
    }

    public function gdpr()
    {
        $settings = CompanySetting::first();
        return view('public.pages.static', [
            'title' => 'GDPR',
            'content' => $settings->gdpr_content,
            'settings' => $settings
        ]);
    }

    public function privacy()
    {
        $settings = CompanySetting::first();
        return view('public.pages.static', [
            'title' => 'Politică de Confidențialitate',
            'content' => $settings->privacy_content,
            'settings' => $settings
        ]);
    }

    public function gallery()
    {
        $settings = CompanySetting::first();
        $albums = GalleryAlbum::query()
            ->where('is_active', true)
            ->with('images')
            ->orderBy('sort_order')
            ->orderByDesc('event_date')
            ->get();

        return view('public.pages.gallery', [
            'title' => 'Galerie Evenimente',
            'albums' => $albums,
            'settings' => $settings
        ]);
    }
}
