<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOption extends Model
{
    protected $fillable = ['title'];

    public function values()
    {
        return $this->hasMany(ItemOptionValue::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_option_item')
            ->withPivot(['size_id', 'option_type', 'is_counter', 'min_count', 'max_count']);
    }
}
