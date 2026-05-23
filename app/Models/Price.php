<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $guarded = ["id"];

    public function entity()
    {
        return $this->morphTo();
    }
}
