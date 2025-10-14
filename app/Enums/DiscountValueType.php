<?php

namespace App\Enums;

enum DiscountValueType: string
{
    case PERCENTAGE = "percentage";
    case FIXED = "fixed";
}
