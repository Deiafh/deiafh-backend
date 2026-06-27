<?php

namespace App\Models;

use App\Services\WorkingPeriodsService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $appends = ['isWorkingNow'];

    protected $fillable = ['title', 'address', 'google_map_url', 'tax', 'active', 'working_period_group_id', 'menu_group_id',
        'is_delivery_available', 'is_pickup_available', 'is_busy', 'order_time_from', 'order_time_to'];

    protected $casts = [
        'is_delivery_available' => 'boolean',
        'is_pickup_available'   => 'boolean',
        'is_busy'               => 'boolean',
        'order_time_from'       => 'integer',
        'order_time_to'         => 'integer',
    ];

    function locations()
    {
        return $this->hasMany(BranchLocation::class);
    }

    public function menuGroup()
    {
        return $this->belongsTo(MenuGroup::class, 'menu_group_id');
    }

    function workingPeriodGroup()
    {
        return $this->belongsTo(WorkingPeriodGroup::class, 'working_period_group_id');
    }

    public function getIsWorkingNowAttribute()
    {
        return $this->isWorkingNow();
    }

    /** When the currently-open working window closes, or null if not open now. */
    public function currentPeriodEndsAt(): ?Carbon
    {
        return WorkingPeriodsService::getCurrentPeriodEndForGroup($this->working_period_group_id);
    }

    public function isWorkingNow(): bool
    {
        $currentDate = WorkingPeriodsService::getCurrent();

        return WorkingPeriod::where('working_period_group_id', $this->working_period_group_id)
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
}
