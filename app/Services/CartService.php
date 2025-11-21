<?php

namespace App\Services;

use App\Models\Item;

class CartService
{
    public function validateCart($cart) {
        // loop items
        foreach ($cart['items'] as $item) {
            // check item id exists
            $itemExists = Item::find($item['id']);
            if (!$itemExists) {
                return false;
            }
            // check size id exists for item
            $sizeExists = Item::find($item['id'])->sizes()->where('id', $item['size_id'])->exists();
            if (!$sizeExists) {
                return false;
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
}
