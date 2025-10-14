<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\MainPageHeader;
use Illuminate\Http\Request;

class PosterController extends Controller
{
    public function index()
    {
        return MainPageHeader::get()->map(function ($poster) {
            $poster->url = url($poster->url);
            return $poster;
        });
    }
}