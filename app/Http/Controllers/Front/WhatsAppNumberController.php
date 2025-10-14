<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\WhatsAppNumber;
use Illuminate\Http\Request;

class WhatsAppNumberController extends Controller
{
    public function index()
    {
        return WhatsAppNumber::all();
    }
}
