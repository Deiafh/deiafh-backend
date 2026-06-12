<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchLocation;
use Illuminate\Http\Request;

class BranchLocationController extends Controller
{
    public function index($branchId)
    {
        Branch::findOrFail($branchId);

        return response()->json(BranchLocation::where('branch_id', $branchId)->get());
    }

    public function store(Request $request, $branchId)
    {
        Branch::findOrFail($branchId);

        $request->validate([
            'name'   => 'required|string|max:255',
            'price'  => 'required|numeric|min:0',
            'active' => 'required|boolean',
        ]);

        $location = BranchLocation::create([
            'branch_id' => $branchId,
            'name'      => $request->name,
            'price'     => $request->price,
            'active'    => $request->active,
        ]);

        return response()->json($location, 201);
    }

    public function update(Request $request, $branchId, $locationId)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'price'  => 'required|numeric|min:0',
            'active' => 'required|boolean',
        ]);

        $location = BranchLocation::where('id', $locationId)
            ->where('branch_id', $branchId)
            ->firstOrFail();

        $location->update([
            'name'   => $request->name,
            'price'  => $request->price,
            'active' => $request->active,
        ]);

        return response()->json($location);
    }

    public function destroy($branchId, $locationId)
    {
        BranchLocation::where('id', $locationId)
            ->where('branch_id', $branchId)
            ->firstOrFail()
            ->delete();

        return response()->json(['message' => 'تم حذف الموقع بنجاح']);
    }
}
