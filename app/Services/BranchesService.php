<?php

namespace App\Services;

use App\enums\ActiveStatus;
use App\Models\Branch;

class BranchesService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function getAllBranches()
    {
        return Branch::all();
    }

    public function getAllActiveBranches()
    {
        return Branch::where('active', ActiveStatus::Active)->get();
    }


    public static function getAllWorkingBranches()
    {
        $activeBranches = Branch::where('active', ActiveStatus::Active);

        $currentDate = WorkingPeriodsService::getCurrent();
        $isAvailableWorkingPeriods = WorkingPeriodsService::isAvailableGeneralWorkingPeriod();

        $workingBranches = $activeBranches->where(function ($query) use($isAvailableWorkingPeriods,$currentDate) {
            $query->where(function($hasOwnQuery) use ($currentDate) {
                $hasOwnQuery->where('hasOwnWorkingPeriods', true)
                    ->whereHas('OwnWorkingPeriods', function ($q) use($currentDate) {
                        $q->where(function($q) use($currentDate) {
                            $q->whereColumn('from_date', '<', 'to_date')
                                ->where('from_date', '<=', $currentDate)
                                ->where('to_date', '>=', $currentDate);
                        })->orWhere(function($q) use($currentDate) {
                            $q->whereColumn('from_date', '>', 'to_date')
                                ->where('from_date', '<=', $currentDate)
                                ->orWhere('to_date', '>=', $currentDate);
                        });
                    });
            });

            if($isAvailableWorkingPeriods) {
                $query->orWhere(function ($query) {
                    $query->where('hasOwnWorkingPeriods', false);
                });
            }
        });

        return $workingBranches->get();        
    }
}
