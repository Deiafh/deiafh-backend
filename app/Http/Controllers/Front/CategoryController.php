<?php

namespace App\Http\Controllers\Front;

use App\enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CategoryController extends Controller
{
    public function getCategories(Request $request) 
    {
        $branchId = $request->header('branchId');
        
        $now = Carbon::now();
        $categories = Category::with(['items' => function ($query) use($now) {
                $query->where('active', ActiveStatus::Active->value)
                    ->where(function($q) use($now) {
                        $q->whereNull('from')
                            ->orWhere('from', '<=', $now);
                    })
                    ->where(function($q) use($now) {
                        $q->whereNull('to')
                            ->orWhere('to', '>=', $now);
                    })
                    ->orderBy('sort');
            }])
            ->whereHas('items', function ($query) use($now) {
                $query->where('active', ActiveStatus::Active->value)
                    ->where(function($q) use($now) {
                        $q->whereNull('from')
                            ->orWhere('from', '<=', $now);
                    })
                    ->where(function($q) use($now) {
                        $q->whereNull('to')
                            ->orWhere('to', '>=', $now);
                    });
            })
            ->where(function($query) use($now) {
                $query->where('active', ActiveStatus::Active->value)
                    ->where(function($q) use($now) {
                        $q->whereNull('from')
                            ->orWhere('from', '<=', $now);
                    })
                    ->where(function($q) use($now) {
                        $q->whereNull('to')
                            ->orWhere('to', '>=', $now);
                    });
            })
            ->where(function($query) use($branchId) {
                $query->whereDoesntHave('branches')
                    ->orWhereHas('branches', function($q) use($branchId) {
                        $q->where('branch_id', $branchId);
                    });
            })
            ->orderBy('sort')
            ->get();
        return response()->json($categories);
    }
}
