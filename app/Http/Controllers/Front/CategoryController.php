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
                    ->orderBy('sort')
                    ->with(['sizes' => function($q) {
                        $q->with(['priceForBranch' => function($q2) {
                            $q2->where(function($q3) {
                                $q3->where('branch_id', request()->header('branchId'))
                                ->orWhereNull('branch_id');
                            });
                        }])
                        ->select('id', 'item_id', 'title');
                    }, 'options' => function($q) {
                        $q->with(['values' => function($q2) {
                            $q2->with(['priceForBranch' => function($q3) {
                                $q3->where(function($q4) {
                                    $q4->where('branch_id', request()->header('branchId'))
                                    ->orWhereNull('branch_id');
                                });
                            }])
                            ->select('id', 'item_option_id', 'title');
                        }])
                        ->select('id', 'item_id', 'title', 'size_id', 'option_type', 'is_counter', 'min_count', 'max_count');
                    }])
                    ->with(['priceForBranch' => function($q) {
                        $q->where(function($q2) {
                            $q2->where('branch_id', request()->header('branchId'))->orWhereNull('branch_id');
                        });
                    }]);
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
