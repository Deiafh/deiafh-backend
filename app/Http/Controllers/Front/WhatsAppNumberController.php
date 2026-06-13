<?php

namespace App\Http\Controllers\Front;

use App\Enums\NumberType;
use App\Http\Controllers\Controller;
use App\Models\Number;

class WhatsAppNumberController extends Controller
{
    public function index()
    {
        return Number::where('type', NumberType::WhatsApp->value)->get(['id', 'number']);
    }
}
