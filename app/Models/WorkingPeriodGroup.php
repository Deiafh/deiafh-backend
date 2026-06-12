<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingPeriodGroup extends Model
{
    protected $fillable = ['name'];

    public function branches()
    {
        return $this->hasMany(Branch::class, 'working_period_group_id');
    }

    public function workingPeriods()
    {
        return $this->hasMany(WorkingPeriod::class, 'working_period_group_id');
    }
}
