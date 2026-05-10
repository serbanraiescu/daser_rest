<?php

namespace App\Http\Controllers;

use App\Modules\Settings\Models\CompanySetting;
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
        return view('public.pages.gallery', [
            'title' => 'Galerie Evenimente',
            'gallery' => $settings->gallery_content ?? [],
            'settings' => $settings
        ]);
    }
}
