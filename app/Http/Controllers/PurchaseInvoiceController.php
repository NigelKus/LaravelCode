<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Validation\Rule;
class PurchaseInvoiceController extends Controller

{

    public function index()
    {

        return view('layouts.transactional.purchase_invoice.index');
    
    }
}