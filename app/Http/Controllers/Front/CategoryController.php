<?php

namespace App\Http\Controllers\Front;

use app\Enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CategoryController extends Controller
{
    public function getCategories(Request $request)
    {
        $branchId = $request->header('branchId');
        $now      = Carbon::now();

        $categories = Category::with(['items' => function ($query) use ($now, $branchId) {
                $query->where('active', ActiveStatus::Active->value)
                    ->where(fn($q) => $q->whereNull('from')->orWhere('from', '<=', $now))
                    ->where(fn($q) => $q->whereNull('to')->orWhere('to', '>=', $now))
                    ->orderBy('sort')
                    ->with(['sizes' => function ($q) use ($branchId) {
                        $q->with(['priceForBranch' => fn($q2) => $q2->where(
                            fn($q3) => $q3->where('branch_id', $branchId)->orWhereNull('branch_id')
                        )])->select('id', 'item_id', 'title');
                    }])
                    ->with(['options' => function ($q) use ($branchId) {
                        $q->with(['values' => function ($q2) use ($branchId) {
                            $q2->with(['priceForBranch' => fn($q3) => $q3->where(
                                fn($q4) => $q4->where('branch_id', $branchId)->orWhereNull('branch_id')
                            )])->select('id', 'item_option_id', 'title');
                        }]);
                    }])
                    ->with(['priceForBranch' => fn($q) => $q->where(
                        fn($q2) => $q2->where('branch_id', $branchId)->orWhereNull('branch_id')
                    )])
                    ->with('labels')
                    ->with(['stockRestrictions' => function ($q) use ($branchId) {
                        $q->where(fn($q2) => $q2->whereNull('branch_id')->orWhere('branch_id', $branchId))
                          ->where(fn($q2) => $q2->whereNull('until')->orWhere('until', '>', now()));
                    }]);
            }])
            ->whereHas('items', fn($q) => $q
                ->where('active', ActiveStatus::Active->value)
                ->where(fn($q2) => $q2->whereNull('from')->orWhere('from', '<=', $now))
                ->where(fn($q2) => $q2->whereNull('to')->orWhere('to', '>=', $now))
            )
            ->where(fn($q) => $q
                ->where('active', ActiveStatus::Active->value)
                ->where(fn($q2) => $q2->whereNull('from')->orWhere('from', '<=', $now))
                ->where(fn($q2) => $q2->whereNull('to')->orWhere('to', '>=', $now))
            )
            ->where(fn($q) => $q
                ->whereDoesntHave('branches')
                ->orWhereHas('branches', fn($q2) => $q2->where('branch_id', $branchId))
            )
            ->orderBy('sort')
            ->get();

        $categories->each(function ($category) {
            $category->items->each(function ($item) {
                $item->is_out_of_stock = $item->stockRestrictions->isNotEmpty();
                $item->makeHidden('stockRestrictions');

                $item->options->each(function ($option) {
                    if ($option->pivot) {
                        $option->size_id     = $option->pivot->size_id;
                        $option->option_type = $option->pivot->option_type;
                        $option->is_counter  = $option->pivot->is_counter;
                        $option->min_count   = $option->pivot->min_count;
                        $option->max_count   = $option->pivot->max_count;
                        $option->makeHidden('pivot');
                    }
                });
            });
        });

        return response()->json($categories);
    }
}
