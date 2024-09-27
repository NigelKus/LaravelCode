<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Validation\Rule;
class PaymentPurchaseController extends Controller

{

    public function index()
    {

        return view('layouts.transactional.payment_purchase.index');
    
    }
}