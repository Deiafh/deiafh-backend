<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOption extends Model
{
    function values() {
        return $this->hasMany(ItemOptionValue::class);
    }
}
