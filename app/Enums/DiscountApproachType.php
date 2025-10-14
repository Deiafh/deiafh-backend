<?php

namespace App\Enums;

enum DiscountApproachType: string
{
    case ALL = "all";
    case DELIVERY = "delivery";
    case PICK_UP = "pick_up";
}
