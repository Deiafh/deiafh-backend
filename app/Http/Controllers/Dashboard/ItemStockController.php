<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemStockRestriction;
use Illuminate\Http\Request;

class ItemStockController extends Controller
{
    public function index()
    {
        return Category::with(['items' => function ($q) {
            $q->with(['stockRestrictions' => function ($q2) {
                $q2->active()->with('branch:id,title');
            }, 'branches:id'])->orderBy('sort');
        }])
            ->orderBy('sort')
            ->get()
            ->map(function ($category) {
                $category->items->transform(function ($item) {
                    return [
                        'id'                 => $item->id,
                        'title'              => $item->title,
                        'image_url'          => $item->image_url,
                        'branch_ids'         => $item->branches->pluck('id')->values(),
                        'stock_restrictions' => $item->stockRestrictions->values(),
                    ];
                });
                return $category;
            });
    }

    public function store(Request $request, $itemId)
    {
        Item::findOrFail($itemId);

        $data = $request->validate([
            'branch_ids'   => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'until'        => 'nullable|date|after:now',
        ]);

        $until = $data['until'] ?? null;

        if (is_null($data['branch_ids'] ?? null)) {
            ItemStockRestriction::where('item_id', $itemId)->whereNotNull('branch_id')->delete();
            ItemStockRestriction::updateOrCreate(
                ['item_id' => $itemId, 'branch_id' => null],
                ['until' => $until]
            );
        } else {
            foreach ($data['branch_ids'] as $branchId) {
                ItemStockRestriction::updateOrCreate(
                    ['item_id' => $itemId, 'branch_id' => $branchId],
                    ['until' => $until]
                );
            }
        }

        return response()->json(['message' => 'تم تعليق الصنف بنجاح']);
    }

    public function destroy($id)
    {
        ItemStockRestriction::findOrFail($id)->delete();
        return response()->json(['message' => 'تم رفع التقييد بنجاح']);
    }
}
