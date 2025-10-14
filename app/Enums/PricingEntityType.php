<?php

namespace App\Enums;

enum PricingEntityType: string
{
    case Item = 'item';
    case Size = 'size';
    case OptionValue = 'option_value';

    public static function getArray(): array
    {
        return [
            self::Item->value,
            self::Size->value,
            self::OptionValue->value,
        ];
    }
}
