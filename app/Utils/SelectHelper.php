<?php

namespace App\Helpers;

use App\Models\Product;

class SelectHelper
{
    /**
     * Get the list of products for select input.
     *
     * @return array
     */
    public static function getProductOptions()
    {
        // Retrieve products from the database
        $products = Product::all();

        // Format the options for the select input
        $options = [];
        foreach ($products as $product) {
            $options[$product->id] = $product->code . ' - ' . $product->collection . ' - ' . $product->weight . ' g';
        }

        return $options;
    }
    
    
}
