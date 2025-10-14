<?php

namespace App\Enums;

enum DiscountPaymentType: string
{
    case ALL = "all";
    case CASH = "cash";
    case VISA = "visa";
}
