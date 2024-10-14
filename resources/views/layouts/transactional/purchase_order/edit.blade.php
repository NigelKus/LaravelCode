@extends('adminlte::page')

@section('title', 'Purchase Order Edit')

@section('content_header')
    <h1>Edit Purchase Order</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <!-- Sales Order Form -->
            <form method="POST" action="{{ route('purchase_order.update', $purchaseOrder->id) }}" class="form-horizontal">
                @csrf
                @method('PUT')

                <!-- supplier Dropdown -->
                <div class="form-group">
                    <label for="supplier_id">Supplier</label>
                    <select class="form-control @error('supplier_id') is-invalid @enderror" id="supplier_id" name="supplier_id" required>
                        <option value="">Select a supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchaseOrder->supplier_id) == $supplier->id ? 'selected' : '' }}>
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
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $purchaseOrder->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Order Date Field -->
                <div class="form-group">
                    <label for="date">Order Date</label>
                    <input type="datetime-local" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ $purchaseOrder->date ? $purchaseOrder->date->format('Y-m-d\TH:i') : '' }}">
                    @error('date')
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
                        <!-- Add Product Button -->
                        <a href="#" id="btn-add-product-line" class="btn btn-sm btn-outline-info btn-labeled">
                            <span class="btn-label"></span>
                            Add Product Line
                        </a>
                        
                        <!-- Products Table -->
                        <table class="table table-bordered mt-3" id="products-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Remaining Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($purchaseOrder->details->isEmpty())
                                <!-- Default empty row if no details exist -->
                                @include('layouts.transactional.purchase_order.partials.product_line_edit', [
                                    'product_id' => '',
                                    'qty' => '',
                                    'price' => '',
                                    'price_total' => '',
                                    'quantity_remaining' => '',
                                ])
                            @else
                                @foreach($purchaseOrder->details as $detail)
                                    @include('layouts.transactional.purchase_order.partials.product_line_edit', [
                                        'product_id' => $detail->product_id,  
                                        'qty' => $detail->quantity,          
                                        'price' => $detail->price,            
                                        'price_total' => $detail->price * $detail->quantity, 
                                        'quantity_remaining' => $detail->quantity_remaining,
                                    ])
                                @endforeach
                            @endif
                            </tbody>
                        </table>

                        <!-- Hidden input to collect products data -->
                        <!-- Hidden Product Line Template -->
                        <table style="display: none;" id="product-line-template">
                            @include('layouts.transactional.purchase_order.partials.product_line_edit', [
                                'product_id' => '',
                                'qty' => '',
                                'price' => '',
                                'price_total' => '',
                                'quantity_remaining' => '',
                            ])
                        </table>

                    </div>
                </div>

                <!-- Form Submit and Cancel Buttons --> 
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary" >Save</button>
                    <a href="{{ route('purchase_order.show', $purchaseOrder->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        var rowCounter = $('#products-table tbody tr').length; // Initialize rowCounter based on existing rows
    
        // Clone the hidden template
        var productLineTemplate = $('#product-line-template tbody').html().trim();
    
        // Initialize existing rows
        initializeRows();
        updateReadonlyState(); 
        
        // Add Product Button Click Event
        $('#btn-add-product-line').on('click', function(e) {
            e.preventDefault();
            rowCounter++;
            addNewProductLine(rowCounter);
        });
    
        function addNewProductLine(id) {
            let productLine = $(productLineTemplate).clone();
    
            // Update the ID and row number
            productLine.find('.product-line-number').text(id);
    
            // Reset input values
            productLine.find('.quantity').val('');
            productLine.find('.discount').val('');
            productLine.find('.price-each').val('');
            productLine.find('.price-total').val('');
    
            // Append the new row to the table
            $('#products-table tbody').append(productLine);
    
            // Initialize Select2 or similar plugin if needed
            productLine.find('.select-product').select2({
                placeholder: 'Select Product',
                allowClear: true
            });
    
        }
    
        function initializeRows() {
            $('#products-table tbody tr').each(function() {
                formatPriceFields($(this)); // Format existing price and total fields
            });
            // Update readonly state for existing rows
        }
    
        // Use event delegation for dynamically added elements
        $('#products-table').on('change', '.select-product', function() {
            var $select = $(this);
            var $row = $select.closest('tr');
            var selectedProductId = $select.val();
    
            // Check if the selected product is already in the table
            if (isProductDuplicate(selectedProductId, $row)) {
                alert('This product has already been added.');
                $select.val('').trigger('change');
                return;
            }
    
            var price = parseFloat($select.find('option:selected').data('price')) || 0;
    
            // Update price fields
            $row.find('.price-each').val(formatNumber(price));
            var quantity = parseInt($row.find('.quantity').val()) || 0;
            $row.find('.price-total').val(formatNumber(price * quantity));
    
        });
    
        // Handle quantity input changes
        $('#products-table').on('input', '.quantity', function() {
            var $row = $(this).closest('tr');
            var quantity = parseInt($(this).val()) || 0; // Fallback to 0 if empty
            var priceEach = parseFloat($row.find('.price-each').val().replace(/,/g, '')); // Remove commas for calculation
            $row.find('.price-total').val(formatNumber(priceEach * quantity));
    
        });
    
        // Remove row
        $('#products-table').on('click', '.del-row', function() {
            $(this).closest('tr').remove();
        });
    
        function isProductDuplicate(productId, $currentRow) {
            var isDuplicate = false;
    
            $('#products-table tbody tr').each(function() {
                var $row = $(this);
                var currentProductId = $row.find('.select-product').val();
    
                if (currentProductId && currentProductId === productId && $row.get(0) !== $currentRow.get(0)) {
                    isDuplicate = true;
                    return false; // Break out of the loop
                }
            });
    
            return isDuplicate;
        }
    
        // Function to format number with thousands separator
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    
        // Function to format price fields
        function formatPriceFields($row) {
            var priceEach = $row.find('.price-each').val();
            var priceTotal = $row.find('.price-total').val();
    
            // Format and set the price and total values with thousands separators
            if (priceEach) {
                $row.find('.price-each').val(formatNumber(parseFloat(priceEach.replace(/,/g, ''))));
            }
            if (priceTotal) {
                $row.find('.price-total').val(formatNumber(parseFloat(priceTotal.replace(/,/g, ''))));
            }
        }
    
        // Function to update readonly state based on quantity remaining
        function updateReadonlyState() {
            $('#products-table tbody tr').each(function() {
                var $row = $(this);
                var quantityRemaining = parseInt($row.find('.quantity-remaining').val()) || 0; // Get remaining quantity
                var quantity = parseInt($row.find('.quantity').val()) || 0; // Get specified quantity
    
                if (quantityRemaining < quantity) {
                    $row.find('.select-product').prop('disabled', true); // Disable product selection
                    $row.find('.quantity').prop('readonly', true); // Set quantity input to read-only
                    $row.find('.del-row').hide(); // Hide the delete button
                } else {
                    $row.find('.select-product').prop('disabled', false); // Enable product selection
                    $row.find('.quantity').prop('readonly', false); // Make quantity input editable
                    $row.find('.del-row').show(); // Show delete button
                }
            });
        }
    });
            $('form').on('submit', function(e) {
            // Enable all select-product elements before validation
            $('#products-table tbody .select-product').prop('disabled', false);

            let isValid = true;
            let hasProduct = $('#products-table tbody tr').length > 0;

            if (!hasProduct) {
                isValid = false;
                alert('You must add at least one product.');
            }

            $('#products-table tbody tr').each(function() {
                let quantity = $(this).find('.quantity').val();
                if (!quantity || quantity < 1) {
                    isValid = false;
                    $(this).find('.quantity').addClass('is-invalid'); // Add invalid class
                } else {
                    $(this).find('.quantity').removeClass('is-invalid'); // Remove invalid class
                }
            });

            if (!isValid) {
                e.preventDefault(); // Prevent form submission
            }
        });
    </script>
    
@stop
