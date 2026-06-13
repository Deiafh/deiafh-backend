<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemStockRestriction extends Model
{
    protected $fillable = ['item_id', 'branch_id', 'until'];

    protected $casts = ['until' => 'datetime'];

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('until')->orWhere('until', '>', now());
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
