<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'name', 'code', 'min_order', 'max_discount', 'max_user_uses', 'max_uses',
        'start_date', 'end_date', 'active', 'discount_type', 'discount_value',
        'discount_value_type', 'public', 'approach', 'payment_method',
        'locations_type', 'categories_type', 'items_type', 'phones_type', 'branches_type',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'public'     => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'discount_branches', 'discount_id', 'branch_id');
    }

    public function locations()
    {
        return $this->belongsToMany(BranchLocation::class, 'discount_locations', 'discount_id', 'location_id');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'discount_items', 'discount_id', 'item_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'discount_categories', 'discount_id', 'category_id');
    }

    public function phones()
    {
        return $this->hasMany(DiscountPhone::class);
    }
}
