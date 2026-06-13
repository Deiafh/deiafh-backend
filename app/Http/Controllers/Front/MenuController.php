<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->header('branchId');
        $branch = Branch::find($branchId);

        if (!$branch || !$branch->menu_group_id) {
            return response()->json([]);
        }

        return Menu::where('menu_group_id', $branch->menu_group_id)
            ->where('active', 'Active')
            ->orderBy('sort')
            ->get(['id', 'url'])
            ->map(function ($menu) {
                $menu->url = url($menu->url);
                return $menu;
            });
    }
}
