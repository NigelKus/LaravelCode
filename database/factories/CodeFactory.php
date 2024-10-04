<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CodeFactory
{
    /**
     * Generate a unique sales order code.
     *
     * @return string
     */
    public static function generateSalesOrderCode()
    {
        // Get today's date in the format yyyy/mm/dd
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        // Get the latest sales order code to determine the next sequential number
        $latestOrder = DB::table('mstr_salesOrder')
            ->where('code', 'like', "SO/{$year}/{$month}/{$day}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        // Determine the next sequential number
        if ($latestOrder) {
            // Extract the sequential number from the latest order code
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // If no previous orders, start with 0001
            $nextNumber = '0001';
        }
    
        // Generate the new sales order code
        return "SO/{$year}/{$month}/{$day}/{$nextNumber}";
    }
    
    public static function generateSalesInvoiceCode()
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        $latestOrder = DB::table('invoice_sales')
            ->where('code', 'like', "SI/{$year}/{$month}/{$day}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
    
        return "SI/{$year}/{$month}/{$day}/{$nextNumber}";
    }
    
    public static function generatePaymentSalesCode()
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        $latestOrder = DB::table('mstr_payment')
            ->where('code', 'like', "PS/{$year}/{$month}/{$day}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
    
        return "PS/{$year}/{$month}/{$day}/{$nextNumber}";
    }
    
    public static function generatePurchaseOrdersCode()
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        $latestOrder = DB::table('purchase_order')
            ->where('code', 'like', "PO/{$year}/{$month}/{$day}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
    
        return "PO/{$year}/{$month}/{$day}/{$nextNumber}";
    }
    
    public static function generatePurchaseInvoiceCode()
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        $latestOrder = DB::table('purchase_invoice')
            ->where('code', 'like', "PI/{$year}/{$month}/{$day}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
    
        return "PI/{$year}/{$month}/{$day}/{$nextNumber}";
    }
    
    public static function generatePaymentPurchaseCode()
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        $latestOrder = DB::table('payment_purchase')
            ->where('code', 'like', "PP/{$year}/{$month}/{$day}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
    
        return "PP/{$year}/{$month}/{$day}/{$nextNumber}";
    }

    public static function transactionCode()
    {
        // Define a fixed date format for the prefix
        $fixedPrefix = "JO/2024/10/04";
    
        // Get the latest transaction code with the fixed prefix
        $latestOrder = DB::table('acct_journals')
            ->where('code', 'like', "{$fixedPrefix}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        // Determine the next sequential number
        if ($latestOrder) {
            // Extract the sequential number from the latest order code
            $lastNumber = (int)substr($latestOrder->code, -9); // Extract the last 9 characters
            $nextNumber = str_pad($lastNumber + 1, 9, '0', STR_PAD_LEFT);
        } else {
            // If no previous orders, start with 000000001
            $nextNumber = str_pad(1, 9, '0', STR_PAD_LEFT);
        }
    
        // Generate the new transaction code
        return "{$fixedPrefix}/{$nextNumber}";
    }
    
}
