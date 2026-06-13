<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchLocation extends Model
{
    protected $fillable = ['branch_id', 'name', 'price', 'price_group_id', 'active'];

    protected $appends = ['effective_price'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function priceGroup()
    {
        return $this->belongsTo(LocationPriceGroup::class, 'price_group_id');
    }

    public function getEffectivePriceAttribute(): float
    {
        if ($this->price_group_id && $this->relationLoaded('priceGroup') && $this->priceGroup) {
            return (float) $this->priceGroup->price;
        }
        return (float) $this->price;
    }
}
