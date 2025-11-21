<?php

namespace App\Models;

use App\Enums\PricingEntityType;
use Illuminate\Database\Eloquent\Model;

class ItemSize extends Model
{
    protected $hidden = ['priceForBranch'];
    protected $appends = ['price'];

    public function getPriceAttribute()
    {
        return $this->priceForBranch->price;
    }

    public function priceForBranch()
    {
        return $this->morphOne(Price::class, 'entity')
            ->where('entity_type', PricingEntityType::Size->value)
            ->orderByRaw("branch_id IS NULL")
            ->orderByDesc('branch_id')
            ->withDefault([
                'price' => 0
            ]);
    }
}
