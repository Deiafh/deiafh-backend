<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
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
}
