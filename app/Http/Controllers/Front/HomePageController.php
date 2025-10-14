<?php

namespace App\Http\Controllers\Front;

use App\Enums\NumberType;
use App\Http\Controllers\Controller;
use App\Models\MainPageHeader;
use App\Models\Number;

class HomePageController extends Controller
{
    public function index()
    {
        $posters = MainPageHeader::orderBy('sort')->get()->map(function ($poster) {
            $poster->url = url($poster->url);
            return $poster;
        });

        $whatsAppNumbers = Number::where('type', NumberType::WhatsApp->value)->get(['id', 'number']);
        $phoneNumbers = Number::where('type', NumberType::Phone->value)->get(['id', 'number']);

        return response()->json([
            'posters' => $posters,
            'whatsAppNumbers' => $whatsAppNumbers,
            'phoneNumbers' => $phoneNumbers,
        ]);
    }
}
