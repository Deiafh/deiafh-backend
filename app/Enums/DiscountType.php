<?php

namespace App\Enums;

enum DiscountType: string
{
    case CART_DISCOUNT = 'cart';
    case DELIVERY_DISCOUNT = 'delivery';
}
