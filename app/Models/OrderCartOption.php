<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCartOption extends Model
{
    protected $guarded = ["id"];
    public $timestamps = false;

    public function values()
    {
        return $this->hasMany(OrderCartOptionValue::class);
    }
}
