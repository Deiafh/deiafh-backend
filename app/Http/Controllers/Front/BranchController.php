<?php

namespace App\Http\Controllers\Front;

use App\enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchLocation;

class BranchController extends Controller
{
    public function getBranches()
    {
        $branches = Branch::where('active', ActiveStatus::Active->value)
            ->get(['id', 'title', 'working_period_group_id', 'is_delivery_available', 'is_pickup_available', 'is_busy', 'order_time_from', 'order_time_to'])
            ->map(function($branch) {
                return [
                    'id'                    => $branch->id,
                    'title'                 => $branch->title,
                    'isWorkingNow'          => $branch->isWorkingNow(),
                    'is_delivery_available' => (bool) $branch->is_delivery_available,
                    'is_pickup_available'   => (bool) $branch->is_pickup_available,
                    'is_busy'               => (bool) $branch->is_busy,
                    'order_time_from'       => $branch->order_time_from,
                    'order_time_to'         => $branch->order_time_to,
                ];
            });

        return response()->json($branches);
    }

    public function validateBranch($branchId)
    {
        $branch = Branch::where('id', $branchId)->where('active', ActiveStatus::Active->value)->get(['id', 'title']);

        if ($branch->count() > 0) {
            return response()->json([
                'valid' => true,
                'branch' => $branch,
            ]);
        } else {
            return response()->json([
                'valid' => false,
                'message' => 'Branch not found',
            ], 404);
        }
    }

    public function getBranchDetails($branchId)
    {
        $branch = Branch::where('id', $branchId)->get(['id', 'title', 'working_period_group_id', 'is_delivery_available', 'is_pickup_available', 'is_busy', 'order_time_from', 'order_time_to'])->first();

        if ($branch) {
            $branchData = [
                'id'                    => $branch->id,
                'title'                 => $branch->title,
                'isWorkingNow'          => $branch->isWorkingNow(),
                'is_delivery_available' => (bool) $branch->is_delivery_available,
                'is_pickup_available'   => (bool) $branch->is_pickup_available,
                'is_busy'               => (bool) $branch->is_busy,
                'order_time_from'       => $branch->order_time_from,
                'order_time_to'         => $branch->order_time_to,
            ];
            return response()->json($branchData);
        } else {
            return response()->json([
                'message' => 'Branch not found',
            ], 404);
        }
    }

    public function getLocations($branchId) 
    {
        $locations = BranchLocation::where('branch_id', $branchId)->where('active', true)->get();

        return response()->json($locations);
    }
}
