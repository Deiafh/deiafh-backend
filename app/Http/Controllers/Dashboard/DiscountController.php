<?php

namespace App\Http\Controllers\Dashboard;

use App\enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\BranchLocation;
use App\Models\Category;
use App\Models\Discount;
use App\Models\DiscountPhone;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->size ?? 10;
        $orderBy = $request->orderBy ?? 'id';
        $orderDir = $request->orderDir ?? 'desc';

        $discounts = Discount::when($request->name, fn($q) => $q->where('name', 'like', '%' . $request->name . '%'))
            ->when($request->code, fn($q) => $q->where('code', 'like', '%' . $request->code . '%'))
            ->when($request->active, fn($q) => $q->where('active', $request->active))
            ->when($request->discount_type, fn($q) => $q->where('discount_type', $request->discount_type))
            ->orderBy($orderBy, $orderDir)
            ->paginate($size);

        $discounts->getCollection()->transform(fn($d) => $this->listShape($d));

        return response()->json($discounts);
    }

    public function show($id)
    {
        $discount = Discount::with(['branches', 'locations', 'categories', 'items', 'phones'])->findOrFail($id);

        return response()->json([
            'id'                 => $discount->id,
            'name'               => $discount->name,
            'code'               => $discount->code,
            'active'             => $discount->active,
            'public'             => $discount->public,
            'discount_type'      => $discount->discount_type,
            'discount_value'     => $discount->discount_value,
            'discount_value_type'=> $discount->discount_value_type,
            'max_discount'       => $discount->max_discount,
            'min_order'          => $discount->min_order,
            'max_uses'           => $discount->max_uses,
            'max_user_uses'      => $discount->max_user_uses,
            'start_date'         => $discount->start_date?->format('Y-m-d\TH:i'),
            'end_date'           => $discount->end_date?->format('Y-m-d\TH:i'),
            'approach'           => $discount->approach,
            'payment_method'     => $discount->payment_method,
            'branches_type'      => $discount->branches_type,
            'branch_ids'         => $discount->branches->pluck('id'),
            'locations_type'     => $discount->locations_type,
            'location_ids'       => $discount->locations->pluck('id'),
            'categories_type'    => $discount->categories_type,
            'category_ids'       => $discount->categories->pluck('id'),
            'items_type'         => $discount->items_type,
            'item_ids'           => $discount->items->pluck('id'),
            'phones_type'        => $discount->phones_type,
            'phones'             => $discount->phones->pluck('phone'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validate($request);
        $discount = Discount::create($data);
        $this->syncRelations($discount, $request);

        return response()->json($this->listShape($discount), 201);
    }

    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);
        $data = $this->validate($request);
        $discount->update($data);
        $this->syncRelations($discount, $request);

        return response()->json($this->listShape($discount));
    }

    public function destroy($id)
    {
        Discount::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف الخصم بنجاح']);
    }

    public function toggleActive($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->active = $discount->active === ActiveStatus::Active->value
            ? ActiveStatus::Inactive->value
            : ActiveStatus::Active->value;
        $discount->save();

        return response()->json(['active' => $discount->active]);
    }

    // ---------- helpers ----------

    private function validate(Request $request): array
    {
        return $request->validate([
            'name'                => 'required|string|max:255',
            'code'                => 'required|string|max:100',
            'active'              => 'required|in:active,inactive',
            'public'              => 'required|boolean',
            'discount_type'       => 'required|in:cart,delivery',
            'discount_value'      => 'required|numeric|min:0',
            'discount_value_type' => 'required|in:percentage,fixed',
            'max_discount'        => 'required|numeric|min:0',
            'min_order'           => 'required|numeric|min:0',
            'max_uses'            => 'required|numeric|min:0',
            'max_user_uses'       => 'required|numeric|min:0',
            'start_date'          => 'nullable|date',
            'end_date'            => 'nullable|date',
            'approach'            => 'required|in:all,delivery,pick_up',
            'payment_method'      => 'required|in:all,cash,visa',
            'branches_type'       => 'required|in:all,include,exclude',
            'locations_type'      => 'required|in:all,include,exclude',
            'categories_type'     => 'required|in:all,include,exclude',
            'items_type'          => 'required|in:all,include,exclude',
            'phones_type'         => 'required|in:all,include,exclude',
            'branch_ids'          => 'present|array',
            'branch_ids.*'        => 'integer|exists:branches,id',
            'location_ids'        => 'present|array',
            'location_ids.*'      => 'integer|exists:branch_locations,id',
            'category_ids'        => 'present|array',
            'category_ids.*'      => 'integer|exists:categories,id',
            'item_ids'            => 'present|array',
            'item_ids.*'          => 'integer|exists:items,id',
            'phones'              => 'present|array',
            'phones.*'            => 'string|max:20',
        ]);
    }

    private function syncRelations(Discount $discount, Request $request): void
    {
        $discount->branches()->sync($request->branch_ids ?? []);
        $discount->locations()->sync($request->location_ids ?? []);
        $discount->categories()->sync($request->category_ids ?? []);
        $discount->items()->sync($request->item_ids ?? []);

        $discount->phones()->delete();
        foreach (($request->phones ?? []) as $phone) {
            DiscountPhone::create(['discount_id' => $discount->id, 'phone' => $phone]);
        }
    }

    private function listShape(Discount $discount): array
    {
        return [
            'id'             => $discount->id,
            'name'           => $discount->name,
            'code'           => $discount->code,
            'active'         => $discount->active,
            'public'         => $discount->public,
            'discount_type'  => $discount->discount_type,
            'discount_value' => $discount->discount_value,
            'discount_value_type' => $discount->discount_value_type,
            'max_discount'   => $discount->max_discount,
            'start_date'     => $discount->start_date?->format('Y-m-d'),
            'end_date'       => $discount->end_date?->format('Y-m-d'),
        ];
    }

    // ---------- supporting data for the form ----------

    public function categories()
    {
        return response()->json(
            Category::orderBy('sort')->get(['id', 'title'])
        );
    }

    public function locations()
    {
        return response()->json(
            BranchLocation::with('branch:id,title')->get()->map(fn($l) => [
                'id'     => $l->id,
                'name'   => $l->name,
                'branch' => $l->branch->title ?? '',
            ])
        );
    }
}
