@extends('adminlte::page')

@section('title', 'Edit Purchase Invoice')

@section('content_header')
    <h1>Edit Purchase Invoice</h1>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <!-- purchase Invoice Form -->
            <form method="POST" action="{{ route('purchase_invoice.update', $purchaseInvoice->id) }}" class="form-horizontal">
                @csrf
                @method('PUT')
                
                <!-- purchase Order Code -->
                <div class="form-group">
                    <label for="purchase_order_code">purchase Order Code</label>
                    <input type="text" class="form-control @error('purchase_order_code') is-invalid @enderror" id="purchase_order_code" name="purchase_order_code" value="{{ old('purchase_order_code', $purchaseInvoice->purchaseOrder->code ?? '') }}" readonly>
                    @error('purchase_order_code')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                

                <!-- purchase Invoice Code -->
                <div class="form-group">
                    <label for="invoice_code">Invoice Code</label>
                    <input type="text" class="form-control @error('invoice_code') is-invalid @enderror" id="invoice_code" name="invoice_code" value="{{ old('invoice_code', $purchaseInvoice->code) }}" readonly>
                    @error('invoice_code')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- supplier Dropdown -->
                <div class="form-group">
                    <label for="supplier_id">Supplier</label>
                    <input type="hidden" name="supplier_id" value="{{ old('supplier_id', $purchaseInvoice->supplier_id) }}">
                    <input type="text" class="form-control @error('supplier_id') is-invalid @enderror" 
                            id="supplier_id_display" 
                            value="{{ $suppliers->firstWhere('id', old('supplier_id', $purchaseInvoice->supplier_id))->name ?? 'supplier Not Found' }}" 
                            readonly>
                    @error('supplier_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <!-- Description Field -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $purchaseInvoice->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Order Date Field -->
                <div class="form-group">
                    <label for="date">Purchase Invoice Date</label>
                    <input type="datetime-local" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', $purchaseInvoice->date->format('Y-m-d\TH:i')) }}" >
                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Due Date Field -->
                <div class="form-group">
                    <label for="due_date">Purchase Invoice Due Date</label>
                    <input type="datetime-local" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date', $purchaseInvoice->due_date->format('Y-m-d\TH:i')) }}">
                    @error('due_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Products Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Products</h3>
                    </div>
                    <div class="card-body">
                        <!-- Products Table -->
                        <table class="table table-bordered mt-3" id="products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Requested</th>
                                    <th>Original Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Remaining Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseInvoice->purchaseOrder->details as $purchaseOrderDetail)
                                @php
                                    $quantitySent = $purchaseOrderDetail->quantity_sent;
                                
                                    // Calculate remaining_quantity
                                    $remainingQuantity = $purchaseOrderDetail->quantity - $quantitySent;
                                
                                    // Find the corresponding invoice detail
                                    $invoiceDetail = $purchaseInvoice->details->firstWhere('product_id', $purchaseOrderDetail->product_id);
                                @endphp
                                @include('layouts.transactional.purchase_invoice.partials.product_line_edit', [
                                    'product_id' => $purchaseOrderDetail->product_id,  
                                    'requested' => $invoiceDetail ? $invoiceDetail->quantity : 0, 
                                    'qty' => $invoiceDetail ? $invoiceDetail->quantity : 0, // Check if $invoiceDetail is not null
                                    'price' => $purchaseOrderDetail->price, // Assuming price is stored here
                                    'price_total' => $purchaseOrderDetail->price * $purchaseOrderDetail->quantity, 
                                    'product' => $purchaseOrderDetail->product,  
                                    'quantity_sent' => $remainingQuantity + ($invoiceDetail ? $invoiceDetail->quantity : 0), // Safely access quantity
                                    'purchaseorderdetail_id' => $purchaseOrderDetail->id,
                                    'purchase_order_id' => $purchaseInvoice->purchaseOrder->id
                                ])
                                @endforeach
                                
                                </tbody>
                            </table>
                            

                            <!-- Hidden Product Line Template -->
                            <table style="display: none;" id="product-line-template">
                                @include('layouts.transactional.purchase_invoice.partials.product_line_edit', [
                                    'product_id' => '',
                                    'requested' => '',
                                    'qty' => '',
                                    'price' => '',
                                    'price_total' => '',
                                    'product' => '',
                                    'quantity_sent' => '',
                                    'purchaseorderdetail_id' => '',
                                    'purchase_order_id' => '',
                                ])
                            </table>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('purchase_invoice.show', $purchaseInvoice->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.1/jquery.min.js"
        integrity="sha512-aVKKRRi/Q/YV+4mjoKBsE4x3H+BkegoM/em46NNlCqNTmUYADjBbeNefNxYV7giUp0VxICtqdrbqU7iVaeZNXA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-u1OknCvxWvY5kfmNBILK2hRnQC3Pr17a+RTT6rIHI7NnikvbZlHgTPOOmMi466C8" crossorigin="anonymous"></script>
        <script>
            $(document).ready(function() {
                // Initialize Select2 on the supplier dropdown if it exists

        
                // Format existing price and total fields on page load
                $('#products-table tbody tr').each(function() {
                    var $row = $(this);
                    formatPrice($row.find('.price-each'));
                    formatPrice($row.find('.price-total'));
                });
        
                // Event handler for input changes in the requested field
                $(document).on('input', '.requested', function() {
                    var $this = $(this);
                    var $row = $this.closest('tr');
                    
                    // Convert to numbers
                    var quantitySent = parseFloat($row.find('.quantity_sent').val()) || 0; // Get quantity_sent
                    var requestedQty = parseFloat($this.val()) || 0; // Get requested quantity
                    
                    // Ensure the requested quantity is at least 1 and does not exceed quantity_sent
                    if (requestedQty < 1) {
                        // Optionally handle minimum value here
                    } else if (requestedQty > quantitySent) {
                        $this.val(quantitySent); // Set requested quantity to maximum allowed
                        alert('Requested quantity cannot be greater than the quantity sent.');
                    }
        
                    updateTotal($row); // Update the total price
                });
        
                // Function to update the total price
                function updateTotal($row) {
                    var requestedQty = parseFloat($row.find('.requested').val()) || 0;
                    var priceEach = parseFloat($row.find('.price-each').val().replace(/,/g, '')) || 0; // Remove commas for calculation
                    var total = requestedQty * priceEach;
                    $row.find('.price-total').val(formatNumber(total)); // Format total with thousands separator
                }
        
                // Function to format number with thousands separator
                function formatNumber(num) {
                    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                }
        
                // Function to format existing price fields
                function formatPrice($input) {
                    var value = parseFloat($input.val());
                    if (!isNaN(value)) {
                        $input.val(formatNumber(value));
                    }
                }
        
                // Event handler for row removal
                $(document).on('click', '.del-row', function() {
                    $(this).closest('tr').remove(); // Remove the closest <tr> element
                });
            });
        </script>
@stop
