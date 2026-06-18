<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\PricingEntityType;
use App\Http\Controllers\Controller;
use App\Models\ItemOption;
use App\Models\ItemOptionValue;
use App\Models\Price;
use Illuminate\Http\Request;

class ItemOptionController extends Controller
{
    public function index()
    {
        return response()->json(
            ItemOption::with(['values' => fn($q) => $q->with('prices')])->orderBy('title')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate(['title' => 'required|string|max:255']);
        return response()->json(ItemOption::create($data), 201);
    }

    public function update(Request $request, ItemOption $itemOption)
    {
        $data = $request->validate(['title' => 'required|string|max:255']);
        $itemOption->update($data);
        return response()->json($itemOption);
    }

    public function destroy(ItemOption $itemOption)
    {
        $itemOption->delete();
        return response()->json(['message' => 'deleted']);
    }

    // Values
    public function storeValue(Request $request, ItemOption $itemOption)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
        ]);

        $value = $itemOption->values()->create(['title' => $data['title']]);

        Price::create([
            'entity_type' => PricingEntityType::OptionValue->value,
            'entity_id'   => $value->id,
            'branch_id'   => null,
            'price'       => $data['base_price'],
        ]);

        return response()->json($value->load('prices'), 201);
    }

    public function updateValue(Request $request, ItemOption $itemOption, $valueId)
    {
        $value = $itemOption->values()->findOrFail($valueId);
        $data  = $request->validate([
            'title'      => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
        ]);

        $value->update(['title' => $data['title']]);

        Price::updateOrCreate(
            ['entity_type' => PricingEntityType::OptionValue->value, 'entity_id' => $value->id, 'branch_id' => null],
            ['price' => $data['base_price']]
        );

        return response()->json($value->load('prices'));
    }

    public function destroyValue(ItemOption $itemOption, $valueId)
    {
        $value = $itemOption->values()->findOrFail($valueId);
        $value->prices()->delete();
        $value->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function upsertValueBranchPrice(Request $request, ItemOption $itemOption, $valueId)
    {
        $value = $itemOption->values()->findOrFail($valueId);
        $data  = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'price'     => 'required|numeric|min:0',
        ]);

        $price = Price::updateOrCreate(
            ['entity_type' => PricingEntityType::OptionValue->value, 'entity_id' => $value->id, 'branch_id' => $data['branch_id']],
            ['price' => $data['price']]
        );

        return response()->json($price);
    }

    public function deleteValueBranchPrice(Request $request, ItemOption $itemOption, $valueId)
    {
        $value = $itemOption->values()->findOrFail($valueId);
        $request->validate(['branch_id' => 'required|integer|exists:branches,id']);

        Price::where('entity_type', PricingEntityType::OptionValue->value)
            ->where('entity_id', $value->id)
            ->where('branch_id', $request->branch_id)
            ->delete();

        return response()->json(['message' => 'deleted']);
    }
}
