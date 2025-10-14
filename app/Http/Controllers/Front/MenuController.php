<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request) {
        $branchId = $request->header('branchId');

        return Menu::where('branch_id', $branchId)->orderBy('sort')->get('url')->map(function ($menu) {
            $menu->url = url($menu->url);
            return $menu;
        });
    }
}
