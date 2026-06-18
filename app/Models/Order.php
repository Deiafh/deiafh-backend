<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = ["id"];
    
    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_reference)) {
                $order->order_reference = (string) Str::uuid();
            }
        });
    }

    public function items()
    {
        return $this->hasMany(OrderCart::class);
    }

    public function cancelReason()
    {
        return $this->belongsTo(CancelReason::class);
    }
}
