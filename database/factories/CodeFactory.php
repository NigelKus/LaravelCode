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
        // Get today's date in the format dd-mm-yy
        $datePrefix = Carbon::now()->format('d-m-y');

        // Get the latest sales order code to determine the next sequential number
        $latestOrder = DB::table('mstr_salesOrder')
            ->where('code', 'like', "{$datePrefix}-SO-%")
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
        return "{$datePrefix}-SO-{$nextNumber}";
    }

    public static function generateSalesInvoiceCode()
    {
        $datePrefix = Carbon::now()->format('d-m-y');

        $latestOrder = DB::table('invoice_sales')
            ->where('code', 'like', "{$datePrefix}-SI-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$datePrefix}-SI-{$nextNumber}";
    }

    public static function generatePaymentSalesCode()
    {
        $datePrefix = Carbon::now()->format('d-m-y');

        $latestOrder = DB::table('mstr_payment')
            ->where('code', 'like', "{$datePrefix}-PS-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$datePrefix}-PS-{$nextNumber}";
    }

    public static function generatePurchaseOrdersCode()
    {
        $datePrefix = Carbon::now()->format('d-m-y');

        $latestOrder = DB::table('purchase_order')
            ->where('code', 'like', "{$datePrefix}-PO-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$datePrefix}-PO-{$nextNumber}";
    }

    public static function generatePurchaseInvoiceCode()
    {
        $datePrefix = Carbon::now()->format('d-m-y');

        $latestOrder = DB::table('purchase_invoice')
            ->where('code', 'like', "{$datePrefix}-PI-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$datePrefix}-PI-{$nextNumber}";
    }

    public static function transactionCode()
    {
        $datePrefix = Carbon::now()->format('d-m-y');

        $latestOrder = DB::table('acct_journals')
            ->where('code', 'like', "{$datePrefix}-JI-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$datePrefix}-JI-{$nextNumber}";
    }
}
