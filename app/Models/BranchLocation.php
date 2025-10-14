<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchLocation extends Model
{
    function branch()
    {
        return $this->belongsTo(Branch::class);
    }    
}
