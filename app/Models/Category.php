<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'category_branches', 'category_id', 'branch_id');
    }
}
