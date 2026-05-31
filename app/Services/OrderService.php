<?php

namespace App\Services;

use App\enums\ActiveStatus;
use App\Enums\DiscountPaymentType;
use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Branch;
use App\Models\BranchLocation;
use App\Models\Discount;
use App\Models\Item;
use App\Models\ItemOption;
use App\Models\Order;
use App\Models\Setting;
use InvalidArgumentException;

class OrderService
{
    protected CartService $cartService;

    public function __construct(CartService $cartService) {
        $this->cartService = $cartService;
    }

    public function validateUserInfo($userInfo, $branchId) {
        $settings = Setting::first();

        if (!in_array($userInfo['order_type'], ["delivery", "pickup"])) {
            throw new InvalidArgumentException('Invalid order type.');
        }

        if ($settings->is_pickup_available == 0 && $userInfo['order_type'] == "pickup") {
            throw new InvalidArgumentException('Pickup option is not available.');
        }

        if(trim($userInfo['name']) == null) {
            throw new InvalidArgumentException('Name is required.');
        }

        if(count(explode(' ', trim($userInfo['name']))) < 2) {
            throw new InvalidArgumentException('Please enter your full name.');
        }

        if(strlen(trim($userInfo['name'])) < 3) {
            throw new InvalidArgumentException('Name must be at least 3 characters long.');
        }

        if (!preg_match('/^[\p{L}\s]+$/u', $userInfo['name'])) {
            throw new InvalidArgumentException('Name can only contain letters and spaces.');
        }

        if(strlen(trim($userInfo['phone'])) != 11) {
            throw new InvalidArgumentException('Phone number must be 11 digits long.');
        }

        if(!in_array(substr(trim($userInfo['phone']), 0, 3), ['010', '011', '012', '015'])) {
            throw new InvalidArgumentException('Invalid phone number.');
        }

        if(is_nan($userInfo['phone'])) {
            throw new InvalidArgumentException('Phone number must contain only digits.');
        }

        if(strlen(trim($userInfo['additional_phone'])) > 0) {
            if(strlen(trim($userInfo['additional_phone'])) != 11) {
                throw new InvalidArgumentException('Additional phone number must be 11 digits long.');
            }

            if(!in_array(substr(trim($userInfo['additional_phone']), 0, 3), ['010', '011', '012', '015'])) {
                throw new InvalidArgumentException('Invalid additional phone number.');
            }

            if(is_nan($userInfo['additional_phone'])) {
                throw new InvalidArgumentException('Additional phone number must contain only digits.');
            }
        }

        if($userInfo['order_type'] == "delivery") {
            if(empty(trim($userInfo['location']))) {
                throw new InvalidArgumentException('Location is required for delivery orders.');
            }

            if(is_nan($userInfo['location'])) {
                throw new InvalidArgumentException('Invalid location.');
            }
            
            $location = BranchLocation::where('branch_id', $branchId)
                ->where('id', $userInfo['location'])
                ->first();

            if(empty($location)) {
                throw new InvalidArgumentException('Invalid location.');
            }

            if(empty(trim($userInfo['address']))) {
                throw new InvalidArgumentException('Address is required for delivery orders.');
            }

            if(strlen(trim($userInfo['address'])) < 5) {
                throw new InvalidArgumentException('Address must be at least 5 characters long.');
            }
        }

        if (strlen(trim($userInfo['notes'])) > 100) {
            throw new InvalidArgumentException('Notes cannot exceed 100 characters.');
        }

        if($settings->is_visa_available == 0 && $userInfo['payment_type'] == "visa") {
            throw new InvalidArgumentException('Visa payment option is not available.');
        }
    }

    public function validateOrder($cart, $userInfo, $discountId)
    {
        $isValid = $this->cartService->validateCart($cart);
        if(!$isValid) {
            throw new InvalidArgumentException('Invalid cart data.');
        }
        
        $this->validateUserInfo($userInfo, $cart['branchId']);

        //validate branch
        $branch = Branch::find($cart['branchId']);
        if(empty($branch)) {
            throw new InvalidArgumentException('Invalid branch.');
        }

        if($branch->active == ActiveStatus::Inactive->value) {
            throw new InvalidArgumentException('Selected branch is not active.');
        }

        if(!$branch->isWorkingNow()) {
            throw new InvalidArgumentException('Selected branch is currently closed.');
        }

        //order is available
        $settings = Setting::first();
        if(!$settings->is_order_available) {
            throw new InvalidArgumentException('Orders are currently not being accepted. Please try again later.');
        }

        // min
        if($this->cartService->getTotalCartPrice($cart) < $branch->minimum_order_amount) {
            throw new InvalidArgumentException('The total cart amount does not meet the minimum order amount of ' . $branch->minimum_order_amount . '.');
        }

        if($discountId) {
            $discount = Discount::find($discountId);
            DiscountService::validateDiscountCode($discount, $cart, $userInfo, $this->cartService);
        }
    }

    public function saveCashOrder($cart, $userInfo, $discountId)
    {
        $discount = $discountId ? Discount::find($discountId) : null;
        $total_cart = $this->cartService->getTotalCartPrice($cart);
        $delivery_price = $userInfo['order_type'] == "delivery" ? BranchLocation::find($userInfo['location'])->price : 0;
        $cart_discount_amount = $discount && $discount->discount_type === DiscountType::CART_DISCOUNT->value ? DiscountService::calculateCartDiscountAmount($discount, $cart, $this->cartService) : 0;
        $delivery_discount_amount = $discount && $discount->discount_type === DiscountType::DELIVERY_DISCOUNT->value ? DiscountService::calculateDeliveryDiscountAmount($discount, $delivery_price) : 0;
        
        $settings = Setting::first();

        $total = $total_cart - $cart_discount_amount + $delivery_price - $delivery_discount_amount;
        
        $tax = 0;
        if ($settings->tax > 0) {
            $tax = $total * ($settings->tax / 100);
        }

        $totalAmount = $total + $tax;

        $branch = Branch::find($cart['branchId']);
        $order = Order::create([
            "type" => $userInfo['order_type'] == "delivery" ? OrderType::DELIVERY->value : OrderType::PICK_UP->value,
            "client_name" => trim($userInfo['name']),
            "client_phone" => trim($userInfo['phone']),
            "additional_phone" => trim($userInfo['additional_phone']),
            "branch_id" => $cart['branchId'],
            "branch_name" => $branch->title,
            "location_name" => BranchLocation::find($userInfo['location'])->name ?? null,
            "address" => trim($userInfo['address']),
            "notes" => trim($userInfo['notes']),
            "payment_type" => $userInfo['payment_type'] == "cash" ? DiscountPaymentType::CASH->value : DiscountPaymentType::VISA->value,
            "total_cart_price" => $total_cart,
            "delivery_price" => $delivery_price,
            "tax" => $tax,
            "discount_id" => $discountId,
            "discount_name" => $discount->name ?? null,
            "discount_code" => $discount->code ?? null,
            "order_discount_amount" => $cart_discount_amount,
            "delivery_discount_amount" => $delivery_discount_amount,
            "status" => OrderStatus::PENDING->value,
            "total_amount" => $totalAmount
        ]); 

        foreach($cart['items'] as $item) {
            $branchId = $cart['branchId'];
            $itemData = Item::with('sizes')->with(['priceForBranch' => function($q2) use ($branchId) {
                $q2->where(function($q3) use ($branchId) {
                    $q3->where('branch_id', $branchId)->orWhereNull('branch_id');
                });
            }])->find($item['id']);
            $cartItem = $order->items()->create([
                "item_id" => $itemData->id,
                "item_name" => $itemData->title,
                "item_count" => $item['count'],
                "item_single_price" => isset($item['size_id']) ? $itemData->sizes()->with(['priceForBranch' => function($q2) use ($branchId) {
                    $q2->where(function($q3) use ($branchId) {
                        $q3->where('branch_id', $branchId)->orWhereNull('branch_id');
                    });
                }])->where('id', $item['size_id'])->first()->price : $itemData->price,
                "item_total_price_with_options" => $this->cartService->calculateItemPrice($item, $cart['branchId']),
                "item_size_id" => $item['size_id'] ?? null,
                "item_size_name" => isset($item['size_id']) ? $itemData->sizes()->where('id', $item['size_id'])->first()->title : null,
            ]);

            if(isset($item['options'])) {
                foreach($item['options'] as $option) {
                    $optionData = ItemOption::find($option['id']);
                    $optionItem = $cartItem->options()->create([
                        "option_id" => $option['id'],
                        "option_name" => $optionData->title,
                    ]);

                    // insert option values
                    if(isset($option['values'])) {
                        foreach($option['values'] as $value) {
                            $branchId = $cart['branchId'];
                            $valueData = $optionData->values()->with(['priceForBranch' => function($q2) use ($branchId) {
                                $q2->where(function($q3) use ($branchId) {
                                    $q3->where('branch_id', $branchId)->orWhereNull('branch_id');
                                });
                            }])->where('id', $value['id'])->first();
                            $optionItem->values()->create([
                                "option_value_id" => $valueData->id,
                                "option_value_title" => $valueData->title,
                                "option_value_count" => $value['count'],
                                "option_value_single_price" => $valueData->price,
                            ]);
                        }
                    }
                }
            }
        }


        $order->save();
        return $order;
    }
}