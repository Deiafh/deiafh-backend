<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemSize;
use App\Models\ItemStockRestriction;

class CartService
{
    public function validateCart($cart) {
        $branchId = $cart['branchId'] ?? null;

        // loop items
        foreach ($cart['items'] as $item) {
            // check item id exists
            $itemExists = Item::find($item['id']);
            if (!$itemExists) {
                return false;
            }

            // check item is not out of stock for this branch
            $isOutOfStock = ItemStockRestriction::where('item_id', $item['id'])
                ->where(function ($q) use ($branchId) {
                    $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
                })
                ->active()
                ->exists();
            if ($isOutOfStock) {
                return false;
            }
            // check size id exists for item
            if($item['size_id'] != null) {
                $sizeExists = Item::find($item['id'])->sizes()->where('id', $item['size_id'])->exists();
                if (!$sizeExists) {
                    return false;
                }
            }
            // loop options
            foreach ($item['options'] as $option) {
                // check option id exists for item
                $optionExists = Item::find($item['id'])->options()->where('id', $option['id'])->exists();
                if (!$optionExists) {
                    return false;
                }
                // check if option is related to a size
                if(isset($option['size_id']) && $option['size_id'] != null) {
                    // check if it is the same selected size
                    if($option['size_id'] != $item['size_id']) {
                        return false;
                    }
                }
                // loop values
                foreach ($option['values'] as $value) {
                    $total_values_count = 0;
                    // check value id exists for option
                    $valueExists = Item::find($item['id'])->options()->where('id', $option['id'])
                        ->first()->values()->where('id', $value['id'])->exists();
                    if (!$valueExists) {
                        return false;
                    }
                    // sum total values counts
                    $total_values_count += $value['count'];
                }
                // check min/max values counts
                $optionModel = Item::find($item['id'])->options()->where('id', $option['id'])->first();
                if($optionModel->min_values != null && $total_values_count < $optionModel->min_values) {
                    return false;   
                }
                if($optionModel->max_values != null && $total_values_count > $optionModel->max_values) {
                    return false;
                }
            }
            // check notes length
            if(isset($item['notes']) && strlen($item['notes']) > 100) {
                return false;
            }
        }
        return true;
    }

    public function getTotalCartPrice($cart) {
        $total = 0;
        foreach ($cart['items'] as $item) {
            $total += $this->calculateItemPrice($item, $cart['branchId']);
        }

        return $total;
    }

    public function calculateItemPrice($item, $branchId) {
        return $this->calculateSingleItemPrice($item, $branchId) * $item['count'];
    }

    public function calculateSingleItemPrice($item, $branchId) {
        $price = 0;

        $itemInfo = Item::with('sizes')->with(['priceForBranch' => function($q2) use ($branchId) {
            $q2->where(function($q3) use ($branchId) {
                $q3->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }])->find($item['id']);

        $price = is_numeric($item['size_id']) ? $this->getSizePrice($item['size_id'], $branchId) : $itemInfo->price;

        if(isset($item['options'])) {
            foreach ($item['options'] as $option) {
                foreach ($option['values'] as $value) {
                    $valueInfo = $itemInfo->options()->where('id', $option['id'])->first()
                        ->values()->where('id', $value['id'])->first();
                    $price += $valueInfo->price * $value['count'];
                }
            }
        }

        return $price;
    }

    public function getSizePrice($sizeId, $branchId) {
        return ItemSize::with(['priceForBranch' => function($q2) use ($branchId) {
            $q2->where(function($q3) use ($branchId) {
                $q3->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }])->find($sizeId)->price;
    }

    public function getApplicableCartPrice($cart, $discount) {
        $total = 0;
        foreach($cart['items'] as $item) {
            $itemData = Item::find($item['id']);
            $isIncluded = DiscountService::isItemAppliable($discount, $itemData);
            if ($isIncluded) {
                $total += $this->calculateItemPrice($item, $cart['branchId']);
            }
        }
        return $total;
    }
}
