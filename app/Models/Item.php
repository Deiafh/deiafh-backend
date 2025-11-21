<?php

namespace App\Models;

use App\Enums\PricingEntityType;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $hidden = ['priceForBranch'];
    protected $appends = ['price', 'image_url'];

    public function getImageUrlAttribute()
    {
        return url($this->img);
    }

    public function getPriceAttribute()
    {
        return $this->priceForBranch->price;
    }

    public function priceForBranch()
    {
        return $this->morphOne(Price::class, 'entity')
            ->where('entity_type', PricingEntityType::Item->value)
            ->orderByRaw("branch_id IS NULL")
            ->orderByDesc('branch_id')
            ->withDefault([
                'price' => 0
            ]);
    }

    public function sizes()
    {
        return $this->hasMany(ItemSize::class);
    }

    public function options()
    {
        return $this->hasMany(ItemOption::class);
    }
}
