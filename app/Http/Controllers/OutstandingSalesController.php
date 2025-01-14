<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use App\Models\SalesOrder;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\PDF;
use App\Models\PaymentOrderDetail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OutstandingSalesOrder;
use App\Exports\OutstandingSalesInvoice;

class OutStandingSalesController extends Controller
{
    public function index(Request $request)
    {   
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
        return view('layouts.reports.outstanding_sales.index');
    }

    public function outstandingOrder(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
        $dates = $request['date'];
        $salesOrder = SalesOrder::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        $salesInvoice = SalesInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
            foreach ($salesOrder as $order) {
                $order->total_quantity = $order->details->sum('quantity');
                $relatedInvoices = $salesInvoice->where('salesorder_id', $order->id);
                $order->total_quantity_sent = $relatedInvoices->sum(function ($invoice) {
                    return $invoice->details->sum('quantity');
                });
                $order->quantity_difference = $order->total_quantity - $order->total_quantity_sent;

            }
        $salesOrder = $salesOrder->filter(function ($order) {
            return $order->quantity_difference !== 0;
        });
        $displaydate = Carbon::parse($dates)->format('j F Y');
        $createddate = now()->format('j F y H:i:s');
        return view('layouts.reports.outstanding_sales.outstandingOrder', compact('dates', 'salesOrder', 'createddate', 'displaydate'));
    }
    

    public function outstandingInvoice(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }
        $dates = $request['date'];
        $createddate = now()->format('j F y H:i:s');
        $salesInvoice = SalesInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        $salesPayment = PaymentOrderDetail::with('paymentOrder')
            ->whereHas('paymentOrder', function($query) use ($dates) {
                $query->whereDate('date', '<=', $dates);
            })
            ->get();
        foreach ($salesInvoice as $invoice) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity; 
            });
            $relatedPayments = $salesPayment->where('invoicesales_id', $invoice->id);
            $invoice->paid = $relatedPayments->sum(function ($payment) {
                return $payment->price; 
            });
            $invoice->remaining_price = $invoice->total_price - $invoice->paid;

        }
        $salesInvoice = $salesInvoice->filter(function ($invoice) {
            return $invoice->remaining_price !== 0;
        });
        $displaydate = Carbon::parse($dates)->format('j F Y');
        return view('layouts.reports.outstanding_sales.outstandingInvoice', compact('dates', 'salesInvoice', 'createddate', 'displaydate'));
    }
    
    public function generateOrderPdf(Request $request)
    {   
        $dates = $request['dates'];
        $createddate = now()->format('j F y H:i:s');

        $salesOrder = SalesOrder::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $salesInvoice = SalesInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
    
            foreach ($salesOrder as $order) {
                $order->total_quantity = $order->details->sum('quantity');
                
                $relatedInvoices = $salesInvoice->where('salesorder_id', $order->id);

                $order->total_quantity_sent = $relatedInvoices->sum(function ($invoice) {
                    return $invoice->details->sum('quantity');
                });

                $order->quantity_difference = $order->total_quantity - $order->total_quantity_sent;

            }
    
        $salesOrder = $salesOrder->filter(function ($order) {
            return $order->quantity_difference !== 0;
        });


        $dates = Carbon::parse($dates)->format('j F Y');
        $pdf = PDF::loadView('layouts.reports.outstanding_sales.pdfOrder', compact('dates', 'salesOrder', 'createddate'));
        return $pdf->stream('outstanding-sales-order.pdf');
    }

    public function generateInvoicePdf(Request $request)
    {   
        $dates = $request['dates'];
        $createddate = now()->format('j F y H:i:s');

        $salesInvoice = SalesInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $salesPayment = PaymentOrderDetail::with('paymentOrder')
            ->whereHas('paymentOrder', function($query) use ($dates) {
                $query->whereDate('date', '<=', $dates);
            })
            ->get();
    
        foreach ($salesInvoice as $invoice) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity; 
            });

            $relatedPayments = $salesPayment->where('invoicesales_id', $invoice->id);
    
            $invoice->paid = $relatedPayments->sum(function ($payment) {
                return $payment->price; 
            });
    
            $invoice->remaining_price = $invoice->total_price - $invoice->paid;

        }

        $salesInvoice = $salesInvoice->filter(function ($invoice) {
            return $invoice->remaining_price !== 0;
        });

        $dates = Carbon::parse($dates)->format('j F Y');
        $pdf = PDF::loadView('layouts.reports.outstanding_sales.pdfInvoice', compact('dates', 'salesInvoice', 'createddate'));
        return $pdf->stream('outstanding-sales-invoice.pdf');
    }

    public function generateOrderExcel(Request $request)
    {   
        $dates = $request['dates'];
        $createddate = now()->format('j F y H:i:s');
        $displaydate = Carbon::parse($dates)->format('j F Y');
        
        $salesOrder = SalesOrder::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $salesInvoice = SalesInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
    
            foreach ($salesOrder as $order) {
                $order->total_quantity = $order->details->sum('quantity');
                
                $relatedInvoices = $salesInvoice->where('salesorder_id', $order->id);

                $order->total_quantity_sent = $relatedInvoices->sum(function ($invoice) {
                    return $invoice->details->sum('quantity');
                });

                $order->quantity_difference = $order->total_quantity - $order->total_quantity_sent;

            }
    
        $salesOrder = $salesOrder->filter(function ($order) {
            return $order->quantity_difference !== 0;
        });

        return Excel::download(new OutstandingSalesOrder($salesOrder, $dates, $createddate, $displaydate), 'Outstanding Sales Order.xlsx');
    }

    public function generateInvoiceExcel(Request $request)
    {   
        $dates = $request['dates'];
        $createddate = now()->format('j F y H:i:s');
        $displaydate = Carbon::parse($dates)->format('j F Y');
        
        $salesInvoice = SalesInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $salesPayment = PaymentOrderDetail::with('paymentOrder')
            ->whereHas('paymentOrder', function($query) use ($dates) {
                $query->whereDate('date', '<=', $dates);
            })
            ->get();
    
        foreach ($salesInvoice as $invoice) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity; 
            });

            $relatedPayments = $salesPayment->where('invoicesales_id', $invoice->id);
    
            $invoice->paid = $relatedPayments->sum(function ($payment) {
                return $payment->price; 
            });
    
            $invoice->remaining_price = $invoice->total_price - $invoice->paid;

        }

        $salesInvoice = $salesInvoice->filter(function ($invoice) {
            return $invoice->remaining_price !== 0;
        });

        return Excel::download(new OutstandingSalesInvoice($salesInvoice, $dates, $createddate, $displaydate), 'Outstanding Sales Invoice.xlsx');
    }
}