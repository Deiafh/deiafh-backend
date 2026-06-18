<?php

namespace App\Models;

use App\Enums\PricingEntityType;
use Illuminate\Database\Eloquent\Model;

class ItemOptionValue extends Model
{
    protected $fillable = ['title', 'item_option_id'];

    protected $hidden  = ['priceForBranch'];
    protected $appends = ['price'];

    public function getPriceAttribute()
    {
        return $this->priceForBranch->price ?? 0;
    }

    public function priceForBranch()
    {
        return $this->morphOne(Price::class, 'entity')
            ->where('entity_type', PricingEntityType::OptionValue->value)
            ->orderByRaw('branch_id IS NULL ASC')
            ->orderByDesc('branch_id')
            ->withDefault(['price' => 0]);
    }

    public function prices()
    {
        return $this->morphMany(Price::class, 'entity')
            ->where('entity_type', PricingEntityType::OptionValue->value);
    }
}
