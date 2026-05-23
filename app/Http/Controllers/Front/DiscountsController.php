<?php

namespace App\Http\Controllers\Front;

use App\enums\ActiveStatus;
use App\Enums\DiscountApproachType;
use App\Enums\DiscountPaymentType;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Services\{CartService, DiscountService};
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DiscountsController extends Controller
{
    public function getPublicDiscounts(Request $request, CartService $cartService)
    {
        $currentBranch = $request->cart['branchId'];

        $locationId = $request->userInfo['location'] ?? [];

        $userInfo = $request->userInfo;

        $cart = $request->cart;

        $publicDiscounts = Discount::where('public', true);

        //working period discounts
        $activeDiscounts = $publicDiscounts->where('active', ActiveStatus::Active->value);

        $now = Carbon::now();

        $workingDiscounts = $activeDiscounts->where(function($q) use($now) {
                $q->whereNull('start_date')
                ->orWhere('start_date', '<=', $now);
            })
            ->where(function($q) use($now) {
                $q->whereNull('end_date')
                ->orWhere('end_date', '>=', $now);
            });

        // min order
        $cartPrice = $cartService->getTotalCartPrice($request->cart);

        $minPriceDiscounts = $workingDiscounts->where(function($q) use($cartPrice) {
            $q->where('min_order', 0)
            ->orWhere('min_order', '<=', $cartPrice);
        });

        // max uses
        $maxUsesDiscounts = $minPriceDiscounts->where(function($q) {
            $q->where('max_uses', 0)
            ->orWhereRaw('(SELECT COUNT(*) FROM orders WHERE orders.discount_id = discounts.id) < discounts.max_uses');
        });

        // max uses per user
        $phones = [$userInfo['phone']];
        if (!empty($userInfo['additional_phone'])) {
            $phones[] = $userInfo['additional_phone'];
        }

        $placeholders = implode(',', array_fill(0, count($phones), '?'));
        $bindings = array_merge($phones, $phones);

        $maxUsesPerUserDiscounts = $maxUsesDiscounts->where(function($q) use ($placeholders, $bindings) {
            $q->where('max_user_uses', 0)
            ->orWhereRaw(
                "(SELECT COUNT(*) FROM orders WHERE orders.discount_id = discounts.id AND (orders.client_phone IN ($placeholders) OR orders.client_additional_phone IN ($placeholders))) < discounts.max_user_uses",
                $bindings
            );
        });

        // payment method
        $paymentMethodDiscounts = $maxUsesPerUserDiscounts->where(function($q) use($userInfo) {
            $q->where('payment_method', $userInfo['payment_type'])->orWhere('payment_method', DiscountPaymentType::ALL->value);
        });

        // approach
        $approachDiscounts = $paymentMethodDiscounts->where(function($q) use($userInfo) {
            $q->where('approach', $userInfo['order_type'])->orWhere('approach', DiscountApproachType::ALL->value);
        });

        // branch
        $branchDiscounts = $approachDiscounts->where(function($q) use($currentBranch) {
            $q->whereDoesntHave('branches')
            ->orWhereHas('branches', function($q2) use($currentBranch) {
                $q2->where('branch_id', $currentBranch);
            });
        });

        // location
        $locationDiscounts = $branchDiscounts->where(function($q) use($locationId) {
            $q->whereDoesntHave('locations')
            ->orWhereHas('locations', function($q2) use($locationId) {
                $q2->where('location_id', $locationId);
            });
        });

        $discounts = $locationDiscounts->get();
        
        $appliableDiscounts = [];
        foreach ($discounts as $discount) {
            if (DiscountService::isAppliableToCart($discount, $cart)) {
                $appliableDiscounts[] = $discount;
            }
        }
        
        return response()->json(
            array_map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'code' => $discount->code,
                    'name' => $discount->name,
                ];
            }, $appliableDiscounts)
        );
    }

    public function checkDiscountCode(Request $request, CartService $cartService)
    {
        $discountCode = $request->discountCode;
        $userInfo = $request->userInfo;
        $cart = $request->cart;

        try {
            $discount = Discount::where('code', $discountCode)->first();
            DiscountService::validateDiscountCode($discount, $cart, $userInfo, $cartService);

            return response()->json([
                'id' => $discount->id,
                'code' => $discount->code,
                'name' => $discount->name,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
