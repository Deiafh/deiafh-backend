<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingPeriod extends Model
{
    protected $fillable = ['working_period_group_id', 'from_date', 'to_date'];

    public function workingPeriodGroup()
    {
        return $this->belongsTo(WorkingPeriodGroup::class, 'working_period_group_id');
    }
}
