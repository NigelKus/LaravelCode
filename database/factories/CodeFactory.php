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
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        $latestOrder = DB::table('sales_order')
            ->where('code', 'like', "SO/{$year}/{$month}/{$day}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            
            $nextNumber = '0001';
        }
    
        return "SO/{$year}/{$month}/{$day}/{$nextNumber}";
    }
    
    public static function generateSalesInvoiceCode()
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        $latestOrder = DB::table('sales_invoice')
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
    
        $latestOrder = DB::table('sales_payment')
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
    
        $latestOrder = DB::table('purchase_payment')
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

    public static function generateJournalVoucherCode()
    {
        $year = Carbon::now()->format('Y');
        $month = Carbon::now()->format('m');
        $day = Carbon::now()->format('d');
    
        $latestOrder = DB::table('journal_voucher')
            ->where('code', 'like', "JV/{$year}/{$month}/{$day}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }
    
        return "JV/{$year}/{$month}/{$day}/{$nextNumber}";
    }

    public static function transactionCode()
    {
        $fixedPrefix = "JO/2024/10/04";
    
        $latestOrder = DB::table('acct_journals')
            ->where('code', 'like', "{$fixedPrefix}/%")
            ->orderBy('code', 'desc')
            ->first();
    
        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -9); 
            $nextNumber = str_pad($lastNumber + 1, 9, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = str_pad(1, 9, '0', STR_PAD_LEFT);
        }
    
        return "{$fixedPrefix}/{$nextNumber}";
    }
    
}
