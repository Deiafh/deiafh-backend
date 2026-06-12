<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchLocation extends Model
{
    protected $fillable = ['branch_id', 'name', 'price', 'active'];

    function branch()
    {
        return $this->belongsTo(Branch::class);
    }    
}
