<?php

namespace App\Enums;

enum DiscountConditionsType: string
{
    case All = "all";
    case Exclude = "exclude";
    case Include = "include";

    static function getArray(): array
    {
        return [
            self::All->value,
            self::Exclude->value,
            self::Include->value,
        ];
    }
}
