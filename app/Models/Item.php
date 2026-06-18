<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $guarded = ['id'];

    protected $hidden  = ['priceForBranch'];
    protected $appends = ['price', 'image_url'];

    public function getImageUrlAttribute()
    {
        return url($this->img);
    }

    public function getPriceAttribute()
    {
        return $this->priceForBranch->price ?? 0;
    }

    public function priceForBranch()
    {
        return $this->morphOne(Price::class, 'entity')
            ->orderByRaw('branch_id IS NULL ASC')
            ->orderByDesc('branch_id')
            ->withDefault(['price' => 0]);
    }

    public function prices()
    {
        return $this->morphMany(Price::class, 'entity');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function sizes()
    {
        return $this->hasMany(ItemSize::class);
    }

    public function options()
    {
        return $this->belongsToMany(ItemOption::class, 'item_option_item')
            ->withPivot(['size_id', 'option_type', 'is_counter', 'min_count', 'max_count']);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'item_branches');
    }

    public function stockRestrictions()
    {
        return $this->hasMany(ItemStockRestriction::class);
    }

    public function labels()
    {
        return $this->belongsToMany(Label::class);
    }
}
