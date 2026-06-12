<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;

class SettingController extends Controller
{
    public function show()
    {
        return response()->json(Setting::first());
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title'                       => 'required|string|max:255',
            'keywords'                    => 'nullable|string',
            'description'                 => 'nullable|string',
            'whatsapp_order_phone_number' => 'nullable|string|max:20',
            'order_min'                   => 'numeric|min:0',
            'is_whatsapp_available'       => 'boolean',
            'currency'                    => 'required|string|max:20',
            'time_zone'                   => 'required|string|max:100',
            'dir'                         => 'required|in:rtl,ltr',
            'lang'                        => 'required|in:en,ar',
            'logo'                        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.(4 * 1024 * 1024),
            'background'                  => 'nullable|image|mimes:jpeg,png,jpg,webp|max:'.(4 * 1024 * 1024),
        ]);

        $setting = Setting::first();

        $manager = new ImageManager(new Driver());

        if ($request->hasFile('logo')) {
            $path = 'storage/settings/' . uniqid() . '.webp';
            $manager->read($request->file('logo'))
                ->scale(width: 800)
                ->encode(new AutoEncoder('webp', quality: 80))
                ->save($path);
            if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                Storage::disk('public')->delete($setting->logo);
            }
            $data['logo'] = $path;
        }

        if ($request->hasFile('background')) {
            $path = 'storage/settings/' . uniqid() . '.webp';
            $manager->read($request->file('background'))
                ->scale(width: 1920)
                ->encode(new AutoEncoder('webp', quality: 80))
                ->save($path);
            if ($setting->background && Storage::disk('public')->exists($setting->background)) {
                Storage::disk('public')->delete($setting->background);
            }
            $data['background'] = $path;
        }

        $setting->update($data);

        return response()->json($setting->fresh());
    }
}
