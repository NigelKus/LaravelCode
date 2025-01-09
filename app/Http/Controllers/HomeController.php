<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

    //Weekly Revenue Chart
    $weeklyRevenue = [];
    
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();
    
    $currentWeekStart = $startOfMonth->copy();
    while ($currentWeekStart->lte($endOfMonth)) {
        $currentWeekEnd = $currentWeekStart->copy()->endOfWeek();

        $revenueForWeek = SalesInvoice::with('details')
            ->whereBetween('date', [$currentWeekStart, $currentWeekEnd])
            ->get()
            ->sum(function ($invoice) {
                return $invoice->details->sum(function ($detail) {
                    return $detail->price * $detail->quantity;
                });
            });

        $weeklyRevenue[] = $revenueForWeek;

        $currentWeekStart->addWeek();
    }

    //New Orders
    $salesOrder = SalesOrder::whereDate('created_at', Carbon::today())->count();
    //New Purchases
    $purchaseOrder = PurchaseOrder::whereDate('created_at', Carbon::today())->count();

    //Most Sold Product
    $soldproduct = SalesOrder::with('details')->get();
    $productIdCounts = $soldproduct
        ->flatMap(function ($order) {
            return $order->details;
        })
        ->groupBy('product_id')
        ->map(function ($group) {
            return $group->count();
        });
    $mostSoldProductId = $productIdCounts->sortDesc()->keys()->first();
    $product = Product::where('id', $mostSoldProductId)->first();      
    $mostSoldProductCode = $product->code;  

    //Outstanding Sales
    $outstandingSales = SalesOrder::where('status', "pending")->count();
    //Outstanding Purchases
    $outstandingPurchase = PurchaseOrder::where('status', "pending")->count();

    return view('home', ['weeklyRevenue' => $weeklyRevenue, 
    'salesOrder' => $salesOrder,
    'purchaseOrder' => $purchaseOrder,
    'product' => $mostSoldProductCode,
    'outstandingSales' => $outstandingSales,
    'outstandingPurchase' => $outstandingPurchase]);
    }
}
