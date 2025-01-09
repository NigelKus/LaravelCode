<?php

namespace App\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\PurchaseInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PaymentPurchaseDetail;
use App\Exports\OutstandingPurchaseOrder;
use App\Exports\OutstandingPurchaseInvoice;

class OutStandingPurchaseController extends Controller
{
    public function index(Request $request)
    {   
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }

        return view('layouts.reports.outstanding_purchase.index');
    }

    public function outstandingOrder(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }

        $dates = $request['date'];
    
        $purchaseOrder = PurchaseOrder::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $purchaseInvoice = PurchaseInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
    
            foreach ($purchaseOrder as $order) {
                $order->total_quantity = $order->details->sum('quantity');
                
                $relatedInvoices = $purchaseInvoice->where('purchaseorder_id', $order->id);

                $order->total_quantity_sent = $relatedInvoices->sum(function ($invoice) {
                    return $invoice->details->sum('quantity');
                });

                $order->quantity_difference = $order->total_quantity - $order->total_quantity_sent;

                if ($order->quantity_difference !== 0) {
                    $order->status = 'pending'; 
                }
            }
    
        $purchaseOrder = $purchaseOrder->filter(function ($order) {
            return $order->quantity_difference !== 0;
        });
        
        $displaydate = Carbon::parse($dates)->format('j F Y');
        $createddate = now()->format('j F Y H:i:s');

        return view('layouts.reports.outstanding_purchase.outstandingOrder', compact('dates', 'purchaseOrder', 'createddate', 'displaydate'));
    }
    

    public function outstandingInvoice(Request $request)
    {
        if (!in_array($request->user()->role, ['Admin', 'Accountant'])) {
            abort(403, 'Unauthorized access');
        }

        $dates = $request['date'];
    
        $purchaseInvoice = PurchaseInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $purchasePayment = PaymentPurchaseDetail::with('paymentPurchase')
            ->whereHas('paymentPurchase', function($query) use ($dates) {
                $query->whereDate('date', '<=', $dates);
            })
            ->get();
    
        foreach ($purchaseInvoice as $invoice) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity; 
            });

            $relatedPayments = $purchasePayment->where('invoicepurchase_id', $invoice->id);
    
            $invoice->paid = $relatedPayments->sum(function ($payment) {
                return $payment->price; 
            });
    
            $invoice->remaining_price = $invoice->total_price - $invoice->paid;

            if ($invoice->remaining_price !== 0) {
                $invoice->status = 'pending'; 
            }
        }

        $purchaseInvoice = $purchaseInvoice->filter(function ($invoice) {
            return $invoice->remaining_price !== 0;
        });
    
        $displaydate = Carbon::parse($dates)->format('j F Y');
        $createddate = now()->format('j F Y H:i:s');
        return view('layouts.reports.outstanding_purchase.outstandingInvoice', compact('dates', 'purchaseInvoice', 'createddate', 'displaydate'));
    }
    
    public function generateOrderPdf(Request $request)
    {   
        $dates = $request['dates'];

            $purchaseOrder = PurchaseOrder::with('details')
                ->whereDate('date', '<=', $dates)
                ->get();

            $purchaseInvoice = PurchaseInvoice::with('details')
                ->whereDate('date', '<=', $dates)
                ->get();

            foreach ($purchaseOrder as $order) {
                $order->total_quantity = $order->details->sum('quantity');
                
                $relatedInvoices = $purchaseInvoice->filter(function ($invoice) use ($order) {
                    return $invoice->purchaseorder_id === $order->id;
                });

                $order->total_quantity_sent = $relatedInvoices->sum(function ($invoice) {
                    return $invoice->details->sum('quantity');
                });

                $order->quantity_difference = $order->total_quantity - $order->total_quantity_sent;

                if ($order->quantity_difference !== 0) {
                    $order->status = 'pending'; 
                }
            }

            $purchaseOrder = $purchaseOrder->filter(function ($order) {
                return $order->quantity_difference !== 0;
            });

            $displaydate = Carbon::parse($dates)->format('j F Y');
            $createddate = now()->format('j F Y H:i:s');
        $pdf = PDF::loadView('layouts.reports.outstanding_purchase.pdfOrder', compact('dates', 'purchaseOrder','createddate', 'displaydate'));
        return $pdf->stream('outstanding-purchase-order.pdf');
    }

    public function generateInvoicePdf(Request $request)
    {   
        $dates = $request['dates'];

        $purchaseInvoice = PurchaseInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $purchasePayment = PaymentPurchaseDetail::with('paymentPurchase')
            ->whereHas('paymentPurchase', function($query) use ($dates) {
                $query->whereDate('date', '<=', $dates);
            })
            ->get();
    
        foreach ($purchaseInvoice as $invoice) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity; 
            });

            $relatedPayments = $purchasePayment->where('invoicepurchase_id', $invoice->id);
    
            $invoice->paid = $relatedPayments->sum(function ($payment) {
                return $payment->price; 
            });
    
            $invoice->remaining_price = $invoice->total_price - $invoice->paid;

            if ($invoice->remaining_price !== 0) {
                $invoice->status = 'pending'; 
            }
        }

        $purchaseInvoice = $purchaseInvoice->filter(function ($invoice) {
            return $invoice->remaining_price !== 0;
        });

        $displaydate = Carbon::parse($dates)->format('j F Y');
        $createddate = now()->format('j F Y H:i:s');

        $pdf = PDF::loadView('layouts.reports.outstanding_purchase.pdfInvoice', compact('dates', 'purchaseInvoice','createddate', 'displaydate'));
        return $pdf->stream('outstanding-purchase-invoice.pdf');
    }

    public function generateOrderExcel(Request $request)
    {   
        $dates = $request['dates'];
        
        $purchaseOrder = PurchaseOrder::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $purchaseInvoice = PurchaseInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();

    
            foreach ($purchaseOrder as $order) {
                $order->total_quantity = $order->details->sum('quantity');
                
                $relatedInvoices = $purchaseInvoice->where('purchaseorder_id', $order->id);

                $order->total_quantity_sent = $relatedInvoices->sum(function ($invoice) {
                    return $invoice->details->sum('quantity');
                });

                $order->quantity_difference = $order->total_quantity - $order->total_quantity_sent;

                if ($order->quantity_difference !== 0) {
                    $order->status = 'pending'; 
                }
            }
    
        $purchaseOrder = $purchaseOrder->filter(function ($order) {
            return $order->quantity_difference !== 0;
        });

        $displaydate = Carbon::parse($dates)->format('j F Y');
        $createddate = now()->format('j F Y H:i:s');
        return Excel::download(new OutstandingPurchaseOrder($purchaseOrder, $dates, $displaydate, $createddate), 'Outstanding Purchase Order.xlsx');
    }

    public function generateInvoiceExcel(Request $request)
    {   
        $dates = $request['dates'];
        
        $purchaseInvoice = PurchaseInvoice::with('details')
            ->whereDate('date', '<=', $dates)
            ->get();
        
        $purchasePayment = PaymentPurchaseDetail::with('paymentPurchase')
            ->whereHas('paymentPurchase', function($query) use ($dates) {
                $query->whereDate('date', '<=', $dates);
            })
            ->get();
    
        foreach ($purchaseInvoice as $invoice) {
            $invoice->total_price = $invoice->details->sum(function ($detail) {
                return $detail->price * $detail->quantity; 
            });

            $relatedPayments = $purchasePayment->where('invoicepurchase_id', $invoice->id);
    
            $invoice->paid = $relatedPayments->sum(function ($payment) {
                return $payment->price; 
            });
    
            $invoice->remaining_price = $invoice->total_price - $invoice->paid;

            if ($invoice->remaining_price !== 0) {
                $invoice->status = 'pending'; 
            }
        }

        $purchaseInvoice = $purchaseInvoice->filter(function ($invoice) {
            return $invoice->remaining_price !== 0;
        });

        $displaydate = Carbon::parse($dates)->format('j F Y');
        $createddate = now()->format('j F Y H:i:s');
        return Excel::download(new OutstandingPurchaseInvoice($purchaseInvoice, $dates, $displaydate, $createddate), 'Outstanding Purchase Invoice.xlsx');
    }
}