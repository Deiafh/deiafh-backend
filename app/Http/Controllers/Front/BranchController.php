<?php

namespace App\Http\Controllers\Front;

use App\enums\ActiveStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;

class BranchController extends Controller
{
    public function getBranches()
    {
        $branches = Branch::where('active', ActiveStatus::Active->value)->get(['id', 'title', 'hasOwnWorkingPeriods']);

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
}
