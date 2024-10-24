<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Models\Posting;
use App\Models\Product;
use App\Models\ChartOfAccount;
use App\Models\PurchaseInvoice;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseInvoiceDetail;

class HPPFactory
{
    public static function generateHPP($productid, $date)
    {
        $formattedDateString = str_replace('T', ' ', $date);
    
        $stockQuantity = PurchaseInvoiceDetail::where('product_id', $productid)
            ->whereHas('purchaseInvoice', function($query) use ($formattedDateString) {
                $query->where('date', '<', $formattedDateString);
            })
            ->sum('quantity'); //Total Quantity

        $stockPrice = PurchaseInvoiceDetail::where('product_id', $productid)
            ->whereHas('purchaseInvoice', function($query) use ($formattedDateString) {
                $query->where('date', '<', $formattedDateString);
            })
            ->sum(DB::raw('quantity*price')); // Total Price
    
        $cogs = $stockPrice / $stockQuantity;
    
        return $cogs;
    }
    

}