<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LocationPriceGroup;
use Illuminate\Http\Request;

class LocationPriceGroupController extends Controller
{
    public function index($branchId)
    {
        Branch::findOrFail($branchId);

        return response()->json(
            LocationPriceGroup::where('branch_id', $branchId)
                ->withCount('locations')
                ->orderBy('price')
                ->get()
        );
    }

    public function store(Request $request, $branchId)
    {
        Branch::findOrFail($branchId);

        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $group = LocationPriceGroup::create([
            'branch_id' => $branchId,
            'price'     => $request->price,
        ]);

        return response()->json($group->loadCount('locations'), 201);
    }

    public function update(Request $request, $branchId, $groupId)
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $group = LocationPriceGroup::where('id', $groupId)
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $group->update(['price' => $request->price]);

        return response()->json($group->loadCount('locations'));
    }

    public function destroy($branchId, $groupId)
    {
        $group = LocationPriceGroup::where('id', $groupId)
            ->where('branch_id', $branchId)
            ->withCount('locations')
            ->firstOrFail();

        if ($group->locations_count > 0) {
            return response()->json(['message' => 'لا يمكن حذف المجموعة لأنها مرتبطة بمواقع'], 422);
        }

        $group->delete();

        return response()->json(['message' => 'تم حذف المجموعة بنجاح']);
    }
}
