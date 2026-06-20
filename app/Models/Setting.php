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
        'is_visa_available', 'visa_sandbox_mode', 'is_visa_enabled', 'visa_provider',
        'visa_fixed_fees', 'visa_percentage_fees',
        'qnb_merchant_id', 'qnb_api_password',
        'qnb_sandbox_merchant_id', 'qnb_sandbox_api_password',
        'paymob_secret_key', 'paymob_public_key', 'paymob_hmac', 'paymob_integration_id',
        'paymob_sandbox_secret_key', 'paymob_sandbox_public_key', 'paymob_sandbox_hmac', 'paymob_sandbox_integration_id',
    ];

    protected $casts = [
        'is_whatsapp_available' => 'boolean',
        'is_visa_available'     => 'boolean',
        'visa_sandbox_mode'     => 'boolean',
        'is_visa_enabled'       => 'boolean',
    ];
}
