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
use App\OnlinePayment\PaymentProviders;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payment\PaymobPaymentService;
use App\Services\Payment\QnbPaymentService;
use Exception;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(
        Request $request,
        OrderService $orderService,
        CartService $cartService,
        QnbPaymentService $qnb,
        PaymobPaymentService $paymob
    ) {
        $cart        = $request->cart;
        $userInfo    = $request->userInfo;
        $discountId  = $request->discountId;
        $callbackBaseUrl = rtrim($request->callbackBaseUrl ?? '', '/');

        $settings = Setting::first();

        if (!$settings->is_visa_available) {
            return response()->json(['error' => 'Visa payment is not available.'], 400);
        }

        try {
            $orderService->validateOrder($cart, $userInfo, $discountId);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Calculate totals (mirrors getFinalInfo logic)
        $cartAmount = $cartService->getTotalCartPrice($cart);
        $total      = $cartAmount;

        $areaId         = $userInfo['location'];
        $deliveryAmount = $userInfo['order_type'] === OrderType::DELIVERY->value
            ? BranchLocation::find($areaId)?->load('priceGroup')->effective_price ?? 0
            : 0;
        $total += $deliveryAmount;

        $cartDiscount     = 0;
        $deliveryDiscount = 0;
        if ($discountId) {
            $discount = Discount::find($discountId);
            if ($discount->discount_type == DiscountType::CART_DISCOUNT->value) {
                $applicablePrice = $cartService->getApplicableCartPrice($cart, $discount);
                $cartDiscount    = $discount->discount_value_type == DiscountValueType::PERCENTAGE->value
                    ? $applicablePrice * ($discount->discount_value / 100)
                    : min($discount->discount_value, $applicablePrice);
            } else {
                $deliveryDiscount = $discount->discount_value_type == DiscountValueType::PERCENTAGE->value
                    ? $deliveryAmount * ($discount->discount_value / 100)
                    : min($discount->discount_value, $deliveryAmount);
            }
            $total -= $cartDiscount + $deliveryDiscount;
        }

        $branch = \App\Models\Branch::find($cart['branchId']);
        $tax    = 0;
        if ($branch && $branch->tax > 0) {
            $tax = $total * ($branch->tax / 100);
        }
        $total += $tax;

        $visaFees = $total * ($settings->visa_percentage_fees / 100) + $settings->visa_fixed_fees;
        $total   += $visaFees;

        try {
            $order = $orderService->saveVisaOrder($cart, $userInfo, $discountId, $visaFees);

            $provider    = $settings->visa_provider;
            $ref         = $order->order_reference;
            $webhookUrl  = url('/api/order/webhook/paymob');

            if ($provider === PaymentProviders::Qnb->value) {
                $returnUrl = $callbackBaseUrl . '/payment/result?provider=qnb&ref=' . $ref;
                $result    = $qnb->initiate($ref, $total, $returnUrl, $settings);

                $order->update(['payment_operation_id' => $result['operation_id']]);

                return response()->json([
                    'provider'        => 'qnb',
                    'session_id'      => $result['session_id'],
                    'merchant_id'     => $result['merchant_id'],
                    'order_reference' => $ref,
                    'is_sandbox'      => (bool) $settings->visa_sandbox_mode,
                ]);
            } elseif ($provider === PaymentProviders::Paymob->value) {
                $returnUrl = $callbackBaseUrl . '/payment/result?provider=paymob&ref=' . $ref;
                $result    = $paymob->initiate($ref, $total, $returnUrl, $webhookUrl, trim($userInfo['phone']), $settings);

                $order->update(['payment_operation_id' => $result['operation_id']]);

                return response()->json([
                    'provider'        => 'paymob',
                    'payment_url'     => $result['payment_url'],
                    'order_reference' => $ref,
                ]);
            }

            $order->delete();
            return response()->json(['error' => 'Invalid payment provider configured.'], 500);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function verify(Request $request, OrderService $orderService, PaymobPaymentService $paymob)
    {
        $provider        = $request->provider;
        $orderReference  = $request->order_reference;
        $settings        = Setting::first();

        $order = Order::where('order_reference', $orderReference)
            ->where('payment_type', DiscountPaymentType::VISA->value)
            ->where('payment_verified', false)
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or already verified.'], 404);
        }

        $verified = false;

        if ($provider === PaymentProviders::Qnb->value) {
            $resultIndicator = $request->result_indicator;
            $verified = $order->payment_operation_id && $order->payment_operation_id === $resultIndicator;
        } elseif ($provider === PaymentProviders::Paymob->value) {
            $verified = $paymob->verifyRedirect($request->all(), $settings)
                && ($request->success === 'true' || $request->success === true);
        }

        if (!$verified) {
            return response()->json(['success' => false, 'order_reference' => $orderReference]);
        }

        $orderService->confirmVisaPayment($order);

        return response()->json(['success' => true, 'order_reference' => $orderReference]);
    }

    public function paymobWebhook(Request $request, OrderService $orderService, PaymobPaymentService $paymob)
    {
        $settings      = Setting::first();
        $data          = $request->all();
        $receivedHmac  = $request->header('hmac') ?? ($data['hmac'] ?? '');

        if (!$paymob->verifyWebhook($data, $receivedHmac, $settings)) {
            return response()->json(['error' => 'Invalid HMAC'], 400);
        }

        $obj     = $data['obj'] ?? [];
        $success = $obj['success'] ?? false;
        $ref     = $obj['special_reference'] ?? null;

        if (!$success || !$ref) {
            return response()->json(['status' => 'ignored']);
        }

        $order = Order::where('order_reference', $ref)
            ->where('payment_type', DiscountPaymentType::VISA->value)
            ->where('payment_verified', false)
            ->first();

        if ($order) {
            $orderService->confirmVisaPayment($order);
        }

        return response()->json(['status' => 'ok']);
    }
}
