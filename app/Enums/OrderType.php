<?php

namespace App\Enums;

enum OrderType: string
{
    case DELIVERY = "delivery";
    case PICK_UP = "pick_up";
}
