<?php

namespace App\Services;

use App\Enums\ActiveStatus;
use App\Enums\DiscountApproachType;
use App\Enums\DiscountConditionsType;
use App\Enums\DiscountPaymentType;
use App\Enums\DiscountValueType;
use App\Models\Item;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class DiscountService
{
    public static function validateDiscountCode($discount, $cart, $userInfo, $cartService)
    {
        $currentBranch = $cart['branchId'];
        $locationId = $userInfo['location'] ?? null;

        if ($discount == null) {
            throw new InvalidArgumentException("هذا الخصم غير صحيح");
        }

        if ($discount->active != ActiveStatus::Active->value) {
            throw new InvalidArgumentException("هذا الخصم غير غير متاح حاليا");
        }

        $now = Carbon::now();

        if (
            ($discount->start_date != null && $discount->start_date > $now) ||
            ($discount->end_date != null && $discount->end_date < $now)
        ) {
            throw new InvalidArgumentException("هذا الخصم غير متاح حاليا");
        }

        // min order
        $cartPrice = $cartService->getTotalCartPrice($cart);
        if ($discount->min_order > $cartPrice) {
            throw new InvalidArgumentException("هذا الخصم يتطلب على الاقل طلب رقم " . $discount->min_order . " جنيه من الاصناف المشمولة");
        }

        // max uses
        if($discount->max_uses != 0 && $discount->orders()->count() >= $discount->max_uses) {
            throw new InvalidArgumentException("لقد تخطى هذا الخصم الحد المسموح به");
        }

        // max uses per user
        $phones = [$userInfo['phone'], $userInfo['additional_phone']];
        if (
            $discount->max_user_uses != 0 &&
            $discount->orders()
                ->where(function ($query) use ($phones) {
                    $query->whereIn('client_phone', $phones)
                        ->orWhereIn('client_additional_phone', $phones);
                })
                ->count() >= $discount->max_user_uses
        ) {
            throw new InvalidArgumentException("لقد استخدمت الحد المسموح به لهذا الخصم");
        }   

        // payment method
        if (
            $discount->payment_method != DiscountPaymentType::ALL->value &&
            $discount->payment_method != $userInfo['payment_type']
        ) {
            throw new InvalidArgumentException("هذا الخصم لا يسري على طريقة الدفع المختارة");
        }

        // approach
        if (
            $discount->approach != DiscountApproachType::ALL->value &&
            $discount->approach != $userInfo['approach']
        ) {
            throw new InvalidArgumentException("هذا الخصم غير متاح لطريقة الاستلام المختارة");
        }

        // branch
        if (
            $discount->branches()->count() > 0 &&
            !$discount->branches->contains('id', $currentBranch)
        ) {
            throw new InvalidArgumentException("هذا الخصم غير متاح في الفرع المختار");
        }

        // location
        if (
            $discount->locations()->count() > 0 &&
            !$discount->locations->contains('id', $locationId)
        ) {
            throw new InvalidArgumentException("هذا الخصم غير متاح في منطقة التوصيل المختارة");
        }

        // phones
        if ($discount->phones_type != DiscountConditionsType::All->value) {
            $userPhones = array_filter([$userInfo['phone'] ?? null, $userInfo['additional_phone'] ?? null]);
            $discountPhones = $discount->phones->pluck('phone')->toArray();
            $hasMatch = !empty(array_intersect($userPhones, $discountPhones));

            if ($discount->phones_type == DiscountConditionsType::Include->value && !$hasMatch) {
                throw new InvalidArgumentException("هذا الخصم غير متاح لرقم هاتفك");
            }
            if ($discount->phones_type == DiscountConditionsType::Exclude->value && $hasMatch) {
                throw new InvalidArgumentException("هذا الخصم غير متاح لرقم هاتفك");
            }
        }

        if (!DiscountService::isAppliableToCart($discount, $cart)) {
            throw new InvalidArgumentException("هذا الخصم غير متاح في الاصناف المختارة");
        }
    }

    public static function isAppliableToCart($discount, $cart)
    {
        $appliable = false;

        foreach ($cart['items'] as $item) {
            $itemData = Item::find($item['id']);
            if (self::isItemAppliable($discount, $itemData)) {
                $appliable = true;
                break;
            }
        }

        return $appliable;
    }

    public static function isItemAppliable($discount, $item)
    {
        if (
            $discount->items_type == DiscountConditionsType::Exclude->value &&
            $discount->items->contains($item->id)
        ) {
            return false;
        }

        if (
            $discount->items_type == DiscountConditionsType::Include->value &&
            $discount->items->contains($item->id)
        ) {
            return true;
        }

        if (
            $discount->categories_type == DiscountConditionsType::Exclude->value &&
            $discount->categories->contains($item->category_id)
        ) {
            return false;
        }
 
        if (
            $discount->categories_type == DiscountConditionsType::Include->value &&
            $discount->categories->contains($item->category_id)
        ) {
            return true;
        }

        if (
            $discount->items_type == DiscountConditionsType::All->value &&
            $discount->categories_type == DiscountConditionsType::All->value
        ) {
            return true;
        }

        return false;
    }

    public static function calculateCartDiscountAmount($discount, $cart, $cartService)
    {
        $applicablePrice = $cartService->getApplicableCartPrice($cart, $discount);

        if ($discount->discount_value_type == DiscountValueType::PERCENTAGE->value) {
            $amount = $applicablePrice * ($discount->discount_value / 100);
            if ($discount->max_discount > 0) {
                $amount = min($amount, $discount->max_discount);
            }
            return $amount;
        } else {
            return min($discount->discount_value, $applicablePrice);
        }
    }

    public static function calculateDeliveryDiscountAmount($discount, $deliveryPrice)
    {
        if ($discount->discount_value_type == DiscountValueType::PERCENTAGE->value) {
            return $deliveryPrice * ($discount->discount_value / 100);
        } else {
            return min($discount->discount_value, $deliveryPrice);
        }
    }
}