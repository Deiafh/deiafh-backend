<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Theme;

class ConfigurationsController extends Controller
{
    public function index()
    {
        $settings = Setting::first();
        $settings->logo = url($settings->logo);
        $settings->background = url($settings->background);
        $theme = Theme::first();

        return response()->json([
            'settings' => $settings,
            'theme' => $theme,
        ]);
    }
}
