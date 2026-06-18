<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(Item::class)->orderBy('sort');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'category_branches');
    }
}
