<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'title', 'logo', 'keywords', 'description', 'background',
        'whatsapp_order_phone_number',
        'order_min',
        'is_whatsapp_available', 'currency', 'time_zone', 'dir', 'lang',
        'is_visa_available', 'is_visa_enabled', 'visa_provider',
    ];

    protected $casts = [
        'is_whatsapp_available' => 'boolean',
        'is_visa_available'     => 'boolean',
        'is_visa_enabled'       => 'boolean',
    ];
}
