<?php

namespace App\Models;

use App\Services\WorkingPeriodsService;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $appends = ['isWorkingNow'];

    function locations()
    {
        return $this->hasMany(BranchLocation::class);
    }

    function OwnWorkingPeriods()
    {
        return $this->hasMany(BranchWorkingPeriod::class);
    }


    public function getIsWorkingNowAttribute()
    {
        return $this->isWorkingNow();
    }

    public function isWorkingNow(): bool
    {
        $currentDate = WorkingPeriodsService::getCurrent();
        if ($this->hasOwnWorkingPeriods) {
            return $this->OwnWorkingPeriods()
                ->where(function($q) use ($currentDate) {
                    $q->where(function($q) use($currentDate) {
                        $q->whereColumn('from_date', '<', 'to_date')
                            ->where('from_date', '<=', $currentDate)
                            ->where('to_date', '>=', $currentDate);
                    })->orWhere(function($q) use($currentDate) {
                        $q->whereColumn('from_date', '>', 'to_date')
                            ->where('from_date', '<=', $currentDate)
                            ->orWhere('to_date', '>=', $currentDate);
                    });
                })->exists();
        }

        return WorkingPeriodsService::isAvailableGeneralWorkingPeriod();
    }
}
