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

        // Site not installed / not configured yet — degrade instead of 500-ing.
        if (! $settings) {
            return response()->json([
                'settings' => null,
                'theme' => null,
            ], 503);
        }

        $settings->logo = url($settings->logo);
        $settings->background = url($settings->background);
        $settings->makeHidden([
            'qnb_merchant_id', 'qnb_api_password',
            'paymob_secret_key', 'paymob_public_key', 'paymob_hmac', 'paymob_integration_id',
        ]);
        $theme = Theme::first();

        return response()->json([
            'settings' => $settings,
            'theme' => $theme,
        ]);
    }
}
