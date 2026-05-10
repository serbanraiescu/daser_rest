<?php

namespace App\Modules\Public\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\CompanySetting;
use Illuminate\View\View;

class LandingOneController extends Controller
{
    public function index(): View
    {
        $settings = CompanySetting::first() ?? new CompanySetting();
        
        return view('public.landing.theme-one', compact('settings'));
    }
}
