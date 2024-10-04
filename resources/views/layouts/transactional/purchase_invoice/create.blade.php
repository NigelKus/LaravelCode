@extends('adminlte::page')

@section('title', 'Purchases Invoice Create')

@section('content_header')
    <h1>Purchases Invoice</h1>
@stop

@section('css')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-iYQeCzEYFbKjA/T2uDLTpkwGzCiq6soy8tYaI1GyVh/UjpbCx/TYkiZhlZB6+fzT" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <!-- purchase Order Form -->
            <form method="POST" action="/admin/transactional/purchase_invoice/store" class="form-horizontal">
                @csrf

                <!-- supplier Dropdown -->
                <div class="form-group">
                    <label for="supplier_id">Supplier</label>
                    <select class="form-control @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id" required>
                        <option value="">Select a Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Description Field -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Order Date Field -->
                <div class="form-group">
                    <label for="date">Purchase Invoice Date</label>
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" readonly>
                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="due_date">Purchase Invoice Due Date</label>
                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ \Carbon\Carbon::now()->addDays(3)->format('Y-m-d') }}">
                    @error('due_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                
                <!-- purchase Order Dropdown -->
                <div class="form-group">
                    <label for="purchaseorder_id">Purchase Order</label>
                    <select class="form-control @error('purchaseorder_id') is-invalid @enderror" id="purchaseorder_id" name="purchaseorder_id" required>
                        <option value="">Select a purchase Order</option>
                        @foreach($purchaseOrders as $purchaseOrder)
                            <option value="{{ $purchaseOrder->id }}" {{ old('purchaseorder_id') == $purchaseOrder->id ? 'selected' : '' }}>
                                {{ $purchaseOrder->code }}
                            </option>
                        @endforeach
                    </select>
                    @error('purchaseorder_id')
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
                                    <th>Quantity</th>
                                    <th>Remaining Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Include existing rows dynamically -->
                                @foreach(range(0, 0) as $num)
                                    @include('layouts.transactional.purchase_invoice.partials.product_line', [
                                        'purchase_order_detail_ids' => '',
                                        'product_id' => '',
                                        'requested' => '',
                                        'qty' => '',
                                        'price' => '',
                                        'price_total' => '',
                                        'remaining_quantity' => '',
                                    ])
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Hidden Product Line Template -->
                        <table style="display: none;" id="product-line-template">
                            @include('layouts.transactional.purchase_invoice.partials.product_line', [
                                'purchase_order_detail_ids' => '',
                                'product_id' => '',
                                'requested' => '',
                                'qty' => '',
                                'price' => '',
                                'price_total' => '',
                                'remaining_quantity' => '',
                            ])
                        </table>
                    </div>
                </div>

                <!-- Form Submit Button -->
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('purchase_invoice.index') }}" class="btn btn-secondary">Cancel</a>
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
                // Initialize Select2 on the purchase order dropdown
                $('#purchaseorder_id').select2({
                    placeholder: 'Select a purchase Order',
                    allowClear: true
                });
        
                // Event handler for purchase order dropdown change
                $('#purchaseorder_id').change(function() {
                    var purchaseOrderId = $(this).val(); // Get the selected purchase order ID
                    if (purchaseOrderId) {
                        $.ajax({
                            url: '/admin/transactional/purchase_order/' + purchaseOrderId + '/products', // Adjusted URL
                            type: 'GET',
                            success: function(response) {
                                // Clear the existing rows in the products table
                                $('#products-table tbody').empty();
                                
                                // Check if the products array exists and has elements
                                if (response.products && response.products.length > 0) {
                                    // Append new rows to the products table
                                    response.products.forEach(function(product) {
                                        var requested = product.requested || 1;
                                        var price = product.price || 0;
                                        var totalPrice = requested * price;
                                        var productRow = `
                                            <tr class="product-line" id="product-line-${product.product_id}">
                                                <td>${product.code}</td>
                                                <td><input type="number" name="requested[]" class="form-control requested" value="${0}" min="0" /></td>
                                                <td><input type="number" name="qtys[]" class="form-control quantity" value="${product.quantity || 0}" min="0" readonly/></td>
                                                <td><input type="number" name="remaining_quantity[]" class="form-control remaining_quantity" value="${product.remaining_quantity}" min="0" readonly/></td>
                                                <td><input type="text" name="price_eachs[]" class="form-control price-each" value="${formatNumber(price)}" readonly /></td>
                                                <td><input type="text" name="price_totals[]" class="form-control price-total" value="${formatNumber(totalPrice)}" readonly /></td>
                                                <td>
                                                    <input type="hidden" name="purchase_order_detail_ids[]" value="${product.product_id}" />
                                                    <button type="button" class="btn btn-danger btn-sm del-row">Remove</button>
                                                </td>
                                            </tr>
                                        `;
                                        $('#products-table tbody').append(productRow);
                                    });
                                } else {
                                    // If no products are found, display a message
                                    $('#products-table tbody').append('<tr><td colspan="7">No products found.</td></tr>');
                                }
                            },
                            error: function(xhr) {
                                console.error('Failed to fetch products:', xhr.responseText);
                                $('#products-table tbody').append('<tr><td colspan="7">Error loading products. Please try again later.</td></tr>');
                            }
                        });
                    } else {
                        // Clear the products table if no purchase order is selected
                        $('#products-table tbody').empty();
                    }
                });
        
                // Event handler for input changes in the requested field
                $(document).on('input', '.requested', function() {
                    var $this = $(this);
                    var $row = $this.closest('tr');
                    var maxQty = parseInt($row.find('.remaining_quantity').val(), 10); // Get the quantity
                    var requestedQty = parseInt($this.val(), 10); // Get the requested quantity
                    
                    // Ensure the requested quantity is at least 1 and does not exceed available quantity
                    if (requestedQty < 1) {
                        // $this.val(1); // Reset to minimum value of 1
                        // alert('Requested quantity must be at least 1.');
                    } else if (requestedQty > maxQty) {
                        $this.val(maxQty); // Set requested quantity to maximum allowed
                        alert('Requested quantity cannot be greater than the remaining quantity.');
                    }
                    
                    updateTotal($row); // Update the total price if needed
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
        
                // Event handler for row removal
                $(document).on('click', '.del-row', function() {
                    $(this).closest('tr').remove(); // Remove the closest <tr> element
                });
                // Event handler for supplier dropdown change
                $('#supplier_id').change(function() {
                    var supplierId = $(this).val(); // Get the selected supplier ID
                    // Clear the products table when supplier changes
                    $('#products-table tbody').empty(); // Clear existing products

                    if (supplierId) {
                        $.ajax({
                            url: '/admin/transactional/purchase_order/supplier/' + supplierId + '/orders', // Adjust the URL for your route
                            type: 'GET',
                            success: function(response) {
                                // Clear the existing options in the purchase order dropdown
                                $('#purchaseorder_id').empty().append('<option value="">Select a purchase Order</option>');

                                // Check if the purchase orders array exists and has elements
                                if (response.purchaseOrders && response.purchaseOrders.length > 0) {
                                    // Append new options to the purchase order dropdown
                                    response.purchaseOrders.forEach(function(purchaseOrder) {
                                        $('#purchaseorder_id').append(`
                                            <option value="${purchaseOrder.id}">${purchaseOrder.code}</option>
                                        `);
                                    });
                                } else {
                                    // If no purchase orders are found, display a message
                                    $('#purchaseorder_id').append('<option value="">No purchase orders found.</option>');
                                }
                            },
                            error: function(xhr) {
                                console.error('Failed to fetch purchase orders:', xhr.responseText);
                                $('#purchaseorder_id').empty().append('<option value="">Error loading purchase orders. Please try again later.</option>');
                            }
                        });
                    } else {
                        // Clear the purchase order dropdown if no supplier is selected
                        $('#purchaseorder_id').empty().append('<option value="">Select a purchase Order</option>');
                    }
                });


            });
        </script>
        
@stop
