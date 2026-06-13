<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationPriceGroup extends Model
{
    protected $fillable = ['branch_id', 'price'];

    protected $casts = ['price' => 'float'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function locations()
    {
        return $this->hasMany(BranchLocation::class, 'price_group_id');
    }
}
