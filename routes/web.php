<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CoAController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProfitLossController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PaymentOrderController;
use App\Http\Controllers\SalesInvoiceController;
use App\Http\Controllers\GeneralLedgerController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\JournalVoucherController;
use App\Http\Controllers\PaymentPurchaseController;
use App\Http\Controllers\PurchaseInvoiceController;


Route::get('/', function () {
    return view('auth/login');
});

Route::get('customer', function () {
    return view('customer');
});

Auth::routes();

//Home routing
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

//All Master Routing
Route::prefix('admin/master/customer')
    ->name('customer.')
    ->group(function () {
        Route::get('index', [CustomerController::class, 'index'])->name('index');
        Route::get('create', [CustomerController::class, 'create'])->name('create');
        Route::post('store', [CustomerController::class, 'store'])->name('store');
        Route::get('{id}', [CustomerController::class, 'show'])->name('show');
        Route::get('{id}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('{id}', [CustomerController::class, 'update'])->name('update');
        Route::delete('{id}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::post('{id}/update-status', [CustomerController::class, 'updateStatus'])->name('updateStatus');
    });

Route::prefix('admin/master/product')
    ->name('product.')
    ->group(function () {
        Route::get('index', [ProductController::class, 'index'])->name('index');
        Route::get('create', [ProductController::class, 'create'])->name('create');
        Route::post('store', [ProductController::class, 'store'])->name('store');
        Route::get('{id}', [ProductController::class, 'show'])->name('show');
        Route::get('{id}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('{id}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('{id}/update-status', [ProductController::class, 'updateStatus'])->name('updateStatus');
    });

    Route::prefix('admin/master/user')
    ->name('user.')
    ->group(function () {
        Route::get('index', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
    });

    Route::prefix('admin/master/supplier')
    ->name('supplier.')
    ->group(function () {
        Route::get('index', [SupplierController::class, 'index'])->name('index');
        Route::get('create', [SupplierController::class, 'create'])->name('create');
        Route::post('store', [SupplierController::class, 'store'])->name('store');
        Route::get('{id}', [SupplierController::class, 'show'])->name('show');
        Route::get('{id}/edit', [SupplierController::class, 'edit'])->name('edit');
        Route::put('{id}', [SupplierController::class, 'update'])->name('update');
        Route::delete('{id}', [SupplierController::class, 'destroy'])->name('destroy');
        Route::post('{id}/update-status', [SupplierController::class, 'updateStatus'])->name('updateStatus');
    });

    Route::prefix('admin/master/CoA')
    ->name('CoA.')
    ->group(function () {
        Route::get('index', [CoAController::class, 'index'])->name('index');
        Route::get('create', [CoAController::class, 'create'])->name('create');
        Route::post('store', [CoAController::class, 'store'])->name('store');
        Route::get('{id}', [CoAController::class, 'show'])->name('show');
        Route::get('{id}/edit', [CoAController::class, 'edit'])->name('edit');
        Route::put('{id}', [CoAController::class, 'update'])->name('update');
        Route::delete('{id}', [CoAController::class, 'destroy'])->name('destroy');
        Route::post('{id}/update-status', [CoAController::class, 'updateStatus'])->name('updateStatus');
        Route::get('transaction/{posting}', [CoAController::class, 'routeTransaction'])->name('transaction.route');
    });

//All Transactional Routing
    Route::prefix('admin/transactional/sales_order')
    ->name('sales_order.')
    ->group(function () {
        Route::get('index', [SalesOrderController::class, 'index'])->name('index');
        Route::get('create-copy', [SalesOrderController::class, 'create'])->name('create-copy');
        Route::post('store', [SalesOrderController::class, 'store'])->name('store');
        Route::get('{id}/edit', [SalesOrderController::class, 'edit'])->name('edit');
        Route::put('{id}', [SalesOrderController::class, 'update'])->name('update');
        Route::get('{id}', [SalesOrderController::class, 'show'])->name('show');
        Route::patch('{id}/update-status', [SalesOrderController::class, 'updateStatus'])->name('update_status');
        Route::get('{id}/products', [SalesOrderController::class, 'getProducts'])->name('products');
        Route::get('customer/{customerId}/orders', [SalesOrderController::class, 'getSalesOrdersByCustomer'])
            ->name('customer.orders'); // New route for fetching sales orders
        Route::delete('{id}', [SalesOrderController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/transactional/sales_invoice')
        ->name('sales_invoice.')
        ->group(function () {
            Route::get('index', [SalesInvoiceController::class, 'index'])->name('index');
            Route::get('create', [SalesInvoiceController::class, 'create'])->name('create');
            Route::post('store', [SalesInvoiceController::class, 'store'])->name('store');
            Route::get('{id}', [SalesInvoiceController::class, 'show'])->name('show');
            Route::patch('{id}/update-status', [SalesInvoiceController::class, 'updateStatus'])->name('update_status');
            Route::get('{id}/edit', [SalesInvoiceController::class, 'edit'])->name('edit');
            Route::put('{id}', [SalesInvoiceController::class, 'update'])->name('update');
            Route::delete('{id}', [SalesInvoiceController::class, 'destroy'])->name('destroy');
            Route::get('{id}/details', [SalesInvoiceController::class, 'getInvoiceDetails'])->name('details');
            Route::get('customer/{customerId}/invoices', [SalesInvoiceController::class, 'getSalesInvoicesByCustomer'])
            ->name('customer.invoices'); 
    });

    //PaymentInvoice Routing
    Route::prefix('admin/transactional/purchase_order')
        ->name('purchase_order.')
        ->group(function () {
            Route::get('index', [PurchaseOrderController::class, 'index'])->name('index');
            Route::get('create', [PurchaseOrderController::class, 'create'])->name('create');
            Route::post('store', [PurchaseOrderController::class, 'store'])->name('store');
            Route::get('{id}', [PurchaseOrderController::class, 'show'])->name('show');
            Route::delete('{id}', [PurchaseOrderController::class, 'destroy'])->name('destroy');
            Route::patch('{id}/update-status', action: [PurchaseOrderController::class, 'updateStatus'])->name('update_status');
            Route::get('{id}/edit', [PurchaseOrderController::class, 'edit'])->name('edit');
            Route::put('{id}', [PurchaseOrderController::class, 'update'])->name('update');
            Route::get('supplier/{supplierId}/orders', [PurchaseOrderController::class, 'getPurchaseOrdersBySupplier'])
            ->name('supplier.orders'); // New route for fetching sales orders
            Route::get('{id}/products', [PurchaseOrderController::class, 'getProducts'])->name('products');
    });

    Route::prefix('admin/transactional/purchase_invoice')
        ->name('purchase_invoice.')
        ->group(function () {
            Route::get('index', [PurchaseInvoiceController::class, 'index'])->name('index');
            Route::get('create', [PurchaseInvoiceController::class, 'create'])->name('create');
            Route::post('store', [PurchaseInvoiceController::class, 'store'])->name('store');
            Route::get('{id}', [PurchaseInvoiceController::class, 'show'])->name('show');
            Route::patch('{id}/update-status', [PurchaseInvoiceController::class, 'updateStatus'])->name('update_status');
            Route::get('{id}/edit', [PurchaseInvoiceController::class, 'edit'])->name('edit');
            Route::put('{id}', [PurchaseInvoiceController::class, 'update'])->name('update');
            Route::delete('{id}', [PurchaseInvoiceController::class, 'destroy'])->name('destroy');
            Route::get('{id}/details', [PurchaseInvoiceController::class, 'getInvoiceDetails'])->name('details');
            Route::get('supplier/{supplierId}/invoices', [PurchaseInvoiceController::class, 'getPurchaseInvoicesBySupplier'])
            ->name('supplier.invoices'); 
    });

    Route::prefix('admin/transactional/payment_order')
        ->name('payment_order.')
        ->group(function () {
            Route::get('index', [PaymentOrderController::class, 'index'])->name('index');
            Route::get('create', [PaymentOrderController::class, 'create'])->name('create');
            Route::post('store', [PaymentOrderController::class, 'store'])->name('store');
            Route::delete('{id}', [PaymentOrderController::class, 'destroy'])->name('destroy');
            Route::get('{id}', [PaymentOrderController::class, 'show'])->name('show');
            Route::get('{id}/edit', [PaymentOrderController::class, 'edit'])->name('edit');
            Route::patch('{id}/update-status', [PaymentOrderController::class, 'updateStatus'])->name('update_status');
            Route::get('/customer/{id}/invoices', [PaymentOrderController::class, 'getInvoicesByCustomerId'])->name('customer.invoices');
            Route::put('{id}', [PaymentOrderController::class, 'update'])->name('update');
            
    });

    Route::prefix('admin/transactional/payment_purchase')
    ->name('payment_purchase.')
    ->group(function () {
        Route::get('index', [PaymentPurchaseController::class, 'index'])->name('index');
        Route::get('create', [PaymentPurchaseController::class, 'create'])->name('create');
        Route::post('store', [PaymentPurchaseController::class, 'store'])->name('store');
        Route::delete('{id}', [PaymentPurchaseController::class, 'destroy'])->name('destroy');
        Route::get('{id}', [PaymentPurchaseController::class, 'show'])->name('show');
        Route::get('{id}/edit', [PaymentPurchaseController::class, 'edit'])->name('edit');
        Route::patch('{id}/update-status', [PaymentPurchaseController::class, 'updateStatus'])->name('update_status');
        Route::get('/supplier/{id}/invoices', [PaymentPurchaseController::class, 'getInvoicesBySupplierId'])->name('supplier.invoices');
        Route::put('{id}', [PaymentPurchaseController::class, 'update'])->name('update');
    });

//Reports Routing
    Route::get('generateGeneralLedgerPDF', [App\Http\Controllers\PdfController::class, 'generateGeneralLedgerPDF']);

    Route::prefix('admin/reports/general_ledger')
    ->name('general_ledger.')
    ->group(function () {
        Route::get('index', [GeneralLedgerController::class, 'index'])->name('index');
        Route::post('generate', [GeneralLedgerController::class, 'generate'])->name('generate');
        Route::post('pdf', [GeneralLedgerController::class, 'generateGeneralLedgerPDF'])->name('pdf');
        Route::post('excel', [GeneralLedgerController::class, 'generateGeneralLedgerExcel'])->name('excel');
    });

    Route::prefix('admin/reports/profit_loss')
    ->name('profit_loss.')
    ->group(function () {
        Route::get('index', [ProfitLossController::class, 'index'])->name('index');
    });

    Route::prefix('admin/reports/journal')
    ->name('journal.')
    ->group(function () {
        Route::get('index', [JournalVoucherController::class, 'index'])->name('index');
        Route::get('create', [JournalVoucherController::class, 'create'])->name('create');
        Route::post('store', [JournalVoucherController::class, 'store'])->name('store');
        Route::get('{id}', [JournalVoucherController::class, 'show'])->name('show');
        Route::delete('{id}', [JournalVoucherController::class, 'destroy'])->name('destroy');
        Route::get('{id}/edit', [JournalVoucherController::class, 'edit'])->name('edit');
        Route::patch('{id}/update-status', [JournalVoucherController::class, 'updateStatus'])->name('update_status');
        Route::put('{id}', [JournalVoucherController::class, 'update'])->name('update');
    });
