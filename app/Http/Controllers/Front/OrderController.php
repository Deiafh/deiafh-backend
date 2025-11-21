<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BranchLocation;
use App\Models\Setting;
use App\Services\CartService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function validateCart(Request $request, CartService $cartService) {
        $cart = $request->cart;

        $isValid = $cartService->validateCart($cart);

        return $isValid;
    }

    public function validateUserInfo(Request $request) {
        $userInfo = $request->userInfo;

        $settings = Setting::first();

        if (!in_array($userInfo['order_type'], ["delivery", "pickup"])) {
            return response()->json(['error' => 'Invalid order type.'], 400);
        }

        if ($settings->is_pickup_available == 0 && $userInfo['order_type'] == "pickup") {
            return response()->json(['error' => 'Pickup option is not available.'], 400);
        }

        if(trim($userInfo['name']) == null) {
            return response()->json(['error' => 'Name is required.'], 400);
        }

        if(count(explode(' ', trim($userInfo['name']))) < 2) {
            return response()->json(['error' => 'Please enter your full name.'], 400);
        }

        if(strlen(trim($userInfo['name'])) < 3) {
            return response()->json(['error' => 'Name must be at least 3 characters long.'], 400);
        }

        if (!preg_match('/^[\p{L}\s]+$/u', $userInfo['name'])) {
            return response()->json(['error' => 'Name can only contain letters and spaces.'], 400);
        }

        if(strlen(trim($userInfo['phone'])) != 11) {
            return response()->json(['error' => 'Phone number must be 11 digits long.'], 400);
        }

        if(!in_array(substr(trim($userInfo['phone']), 0, 3), ['010', '011', '012', '015'])) {
            return response()->json(['error' => 'Invalid phone number.'], 400);
        }

        if(is_nan($userInfo['phone'])) {
            return response()->json(['error' => 'Phone number must contain only digits.'], 400);
        }

        if(strlen(trim($userInfo['additional_phone'])) > 0) {
            if(strlen(trim($userInfo['additional_phone'])) != 11) {
                return response()->json(['error' => 'Additional phone number must be 11 digits long.'], 400);
            }

            if(!in_array(substr(trim($userInfo['additional_phone']), 0, 3), ['010', '011', '012', '015'])) {
                return response()->json(['error' => 'Invalid additional phone number.'], 400);
            }

            if(is_nan($userInfo['additional_phone'])) {
                return response()->json(['error' => 'Additional phone number must contain only digits.'], 400);
            }
        }

        if($userInfo['order_type'] == "delivery") {
            if(empty(trim($userInfo['location']))) {
                return response()->json(['error' => 'Location is required for delivery orders.'], 400);
            }

            if(is_nan($userInfo['location'])) {
                return response()->json(['error' => 'Invalid location.'], 400);
            }

            $branchId = $request->header('branchId');
            
            $location = BranchLocation::where('branch_id', $branchId)
                ->where('id', $userInfo['location'])
                ->first();

            if(empty($location)) {
                return response()->json(['error' => 'Invalid location.'], 400);
            }

            if(empty(trim($userInfo['address']))) {
                return response()->json(['error' => 'Address is required for delivery orders.'], 400);
            }

            if(strlen(trim($userInfo['address'])) < 5) {
                return response()->json(['error' => 'Address must be at least 5 characters long.'], 400);
            }
        }

        if (strlen(trim($userInfo['notes'])) > 100) {
            return response()->json(['error' => 'Notes cannot exceed 100 characters.'], 400);
        }

        if($settings->is_visa_available == 0 && $userInfo['payment_type'] == "visa") {
            return response()->json(['error' => 'Visa payment option is not available.'], 400);
        }

        return response(true, 200);
    }
}
