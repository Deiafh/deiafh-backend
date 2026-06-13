<?php

namespace App\Http\Controllers\Front;

use App\Enums\DiscountPaymentType;
use App\Enums\DiscountType;
use App\Enums\DiscountValueType;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\BranchLocation;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function validateCart(Request $request, CartService $cartService) {
        $cart = $request->cart;

        $isValid = $cartService->validateCart($cart);

        return $isValid;
    }

    public function validateUserInfo(Request $request, OrderService $orderService) {
        $userInfo = $request->userInfo;
        $branchId = $request->header('branchId');

        try {
            $orderService->validateUserInfo($userInfo, $branchId);
            
            return response()->json([
                'status' => true
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }

    public function getFinalInfo(Request $request, CartService $cartService) {
        $userInfo = $request->userInfo;
        $cart = $request->cart;
        $discountId = $request->discountId;

        $totalAmount = 0;

        $cartAmount = $cartService->getTotalCartPrice($cart);
        $totalAmount += $cartAmount;

        $areaId = $userInfo['location'];
        $location = BranchLocation::find($areaId);
        
        $deliveryAmount = $userInfo['order_type'] == OrderType::DELIVERY->value ? $location->load('priceGroup')->effective_price : 0;
        $totalAmount += $deliveryAmount;

        $cartDiscount = 0;
        $deliveryDiscount = 0;
        if($discountId) {
            $discount = Discount::find($discountId);
            if ($discount->discount_type == DiscountType::CART_DISCOUNT->value) {
                $applicablePrice = $cartService->getApplicableCartPrice($cart, $discount);

                if($discount->discount_value_type == DiscountValueType::PERCENTAGE->value) {
                    $cartDiscount = $applicablePrice * ($discount->discount_value / 100);
                } else {
                    $cartDiscount = min($discount->discount_value, $applicablePrice);   
                }
            } else {
                if($discount->discount_value_type == DiscountValueType::PERCENTAGE->value) {
                    $deliveryDiscount = $deliveryAmount * ($discount->discount_value / 100);
                } else {
                    $deliveryDiscount = min($discount->discount_value, $deliveryAmount);
                }
            }

            $totalAmount -= $cartDiscount;
            $totalAmount -= $deliveryDiscount;
        }

        $branch = \App\Models\Branch::find($request->header('branchId'));

        $tax = 0;
        if ($branch && $branch->tax > 0) {
            $tax = $totalAmount * ($branch->tax / 100);
        }

        $totalAmount += $tax;

        $settings = Setting::first();
        $visa_fees = 0;
        if ($settings->is_visa_available == 1 && $userInfo['payment_type'] == DiscountPaymentType::VISA->value) {
            $visa_fees = $totalAmount * ($settings->visa_percentage_fees / 100) + $settings->visa_fixed_fees;
        }

        $totalAmount += $visa_fees;

        $orderAvg = $branch && $branch->order_time_from && $branch->order_time_to
            ? "{$branch->order_time_from} - {$branch->order_time_to} دقيقة"
            : ($branch && $branch->order_time_from ? "{$branch->order_time_from}+ دقيقة" : null);

        return response()->json([
            'cartAmount' => $cartAmount,
            'cartDiscount' => $cartDiscount,
            'deliveryAmount' => $deliveryAmount,
            'deliveryDiscount' => $deliveryDiscount,
            'tax' => $tax,
            'visa_fees' => $visa_fees,
            'totalAmount' => $totalAmount,
            'orderAvg' => $orderAvg,
        ], 200);
    }

    public function placeOrder(Request $request, OrderService $orderService) {
        $cart = $request->cart;
        $userInfo = $request->userInfo;
        $discountId = $request->discountId;

        try {
            $orderService->validateOrder($cart, $userInfo, $discountId);
            
            $order = $orderService->saveCashOrder($cart, $userInfo, $discountId);

            return response()->json([
                'success' => true,
                'order_reference' => $order->order_reference,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getOrderDetails(Request $request, $order_reference) {
        $order = Order::where('order_reference', $order_reference)->with(["items" => function ($q) {
            return $q->with(["options" => function ($q2) {
                $q2->with("values");
            }]);
        }])->first();
        return response()->json($order, 200);
    }
}
