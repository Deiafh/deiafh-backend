<?php
namespace App\OnlinePayment;

enum PaymentProviders: string
{
    case Paymob = 'paymob';
    case Qnb = 'qnb';

    public static function values(): array
    {
        return array_map(fn(PaymentProviders $type) => $type->value, self::cases());
    }
}