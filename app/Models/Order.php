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

    /**
     * Compact shape used by the dashboard orders list and the realtime
     * broadcast events. Keep both consumers reading from here.
     */
    public function toListArray(): array
    {
        return [
            'id'                      => $this->id,
            'reference'               => $this->order_reference,
            'type'                    => $this->type,
            'client_name'             => $this->client_name,
            'client_phone'            => $this->client_phone,
            'client_additional_phone' => $this->client_additional_phone,
            'branch_id'               => $this->branch_id,
            'branch_name'             => $this->branch_name,
            'location_name'           => $this->location_name,
            'payment_type'            => $this->payment_type,
            'total_amount'            => $this->total_amount,
            'status'                  => $this->status,
            'created_at'              => $this->created_at?->format('Y-m-d H:i'),
        ];
    }
}
