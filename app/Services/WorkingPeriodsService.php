<?php

namespace App\Services;

use App\Models\WorkingPeriod;

class WorkingPeriodsService
{
    private const daysMapping = ["Saturday" => 0, "Sunday" => 1, "Monday" => 2, "Tuesday" => 3, "Wednesday" => 4, "Thursday" => 5, "Friday" => 6];

    public static function getCurrent()
    {
        $week = date("l");

        $current_date = self::daysMapping[$week] . date("His");

        return $current_date;
    }

    public static function isAvailableGeneralWorkingPeriod() {
        $current_date = self::getCurrent();
        return WorkingPeriod::where(function($q) use($current_date) {
            return $q->whereColumn('from_date', '<', 'to_date')
                ->where('from_date', '<=', $current_date)
                ->where('to_date', '>=', $current_date);
        })->orWhere(function($q) use($current_date) {
            return $q->whereColumn('from_date', '>', 'to_date')
                ->where('from_date', '<=', $current_date)
                ->orWhere('to_date', '>=', $current_date);
        })->exists();
    }
}
