<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;

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

    return view('home', ['weeklyRevenue' => $weeklyRevenue]);
    }
}
