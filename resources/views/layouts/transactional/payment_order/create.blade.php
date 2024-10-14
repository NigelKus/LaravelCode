@extends('adminlte::page')

@section('title', 'Payment Sales Create')

@section('content_header')
    <h1>Create Payment Sales</h1>
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

            <form method="POST" action="/admin/transactional/payment_order/store" class="form-horizontal">
                @csrf

                <!-- Customer Field -->
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <select class="form-control @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                        <option value="">Select a Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="customer_id">Payment Type</label>
                    <select class="form-control @error('payment_type') is-invalid @enderror" id="payment_type" name="payment_type" required>
                        <option value="">Select a Payment Type</option>
                        <option value="kas" >Kas</option>
                        <option value="bank" >Bank</option>
                    </select>
                    @error('customer_id')
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

                <!-- Payment Date Field -->
                <div class="form-group">
                    <label for="date">Payment Date</label>
                    <input type="datetime-local" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ \Carbon\Carbon::now()->addHours(7)->format('Y-m-d\TH:i') }}">
                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Invoice Sales Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Invoice Sales</h3>
                    </div>
                    <div class="card-body">
                        <!-- Invoice Sales Table -->
                        <table class="table table-bordered mt-3" id="invoice-sales-table">
                            <thead>
                                <tr>
                                    <th>Invoice Sales Code</th>
                                    <th>Payment Requested</th>
                                    <th>Original Price</th>
                                    <th>Remaining Price</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(range(0, 0) as $num)
                                    @include('layouts.transactional.payment_order.partials.invoice_line', [
                                        'invoice_id' => '',
                                        'requested' => '',
                                        'original_price' => '',
                                        'remaining_price' => '',
                                    ])
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Hidden Invoice Line Template -->
                        <table style="display: none;" id="invoice-line-template">
                            <tbody>
                                @include('layouts.transactional.payment_order.partials.invoice_line', [
                                    'invoice_id' => '',
                                    'requested' => '',
                                    'original_price' => '',
                                    'remaining_price' => '',
                                ])
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Form Submit and Cancel Buttons -->
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('payment_order.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@stop

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {  
   // Initialize Select2 for customer dropdown  
    $('#customer_id').select2();  
    
    // Trigger when customer selection changes  
    $('#customer_id').change(function() {  
        var customerId = $(this).val(); // Get the selected customer ID  
        
        // Clear the invoice table when the customer changes  
        $('#invoice-sales-table tbody').empty(); // Clear existing invoices  
    
        if (customerId) {  
            $.ajax({  
            url: '/admin/transactional/sales_invoice/customer/' + customerId + '/invoices', // Adjust URL if needed  
            type: 'GET',  
            success: function(response) {  
                // console.log(response);
                // Check if the response contains sales invoices  
                if (response.salesInvoices && response.salesInvoices.length > 0) {  
                    // Append each invoice with its total price  
                    response.salesInvoices.forEach(function(invoice) {  
                        // console.log('Invoice:', invoice);
                    // Create a new row for the invoice  
                    var newRow = `  
                        <tr class="invoice-line">  
                            <td>  
                            <span class="invoice-code">${invoice.code}</span>  
                            <input type="hidden" name="invoice_id[]" value="${invoice.id}" /> <!-- Store the invoice ID in a hidden input -->  
                            </td>  
                            <td><input type="number" class="form-control requested-amount" name="requested[]" min="1" data-remaining="${invoice.remaining_price}"/></td>  
                            <td>  
                            <input type="text" name="original_prices[]" class="form-control original-price" value="${formatNumber(invoice.total_price)}" readonly>  
                            </td>  
                            <td>  
                            <input type="text" name="remaining_prices[]" class="form-control remaining_price" value="${formatNumber(invoice.remaining_price)}" readonly>  
                            </td>  
                            <td><a href="#" class="btn btn-danger btn-remove-invoice-line">Remove</a></td>  
                        </tr>  
                    `;  
                    $('#invoice-sales-table tbody').append(newRow); // Append the new row to the table  
                    });  
                } else {  
                    // If no invoices are found, display a message  
                    $('#invoice-sales-table tbody').append(`<tr><td colspan="5">No sales invoices found.</td></tr>`);  
                }  
            },  
            error: function(xhr) {  
                console.error('Failed to fetch sales invoices:', xhr.responseText);  
                $('#invoice-sales-table tbody').append('<tr><td colspan="5">Error loading sales invoices. Please try again later.</td></tr>');  
            }  
            });  
        } else {  
            // If no customer is selected, reset the table  
            $('#invoice-sales-table tbody').empty();  
        }  
    });  
    
    // Handle the removal of an invoice line  
    $(document).on('click', '.btn-remove-invoice-line', function(e) {  
        e.preventDefault(); // Prevent default link action  
        $(this).closest('tr').remove(); // Remove the row  
    });  
    
    // Format numbers with thousand separators  
    $(document).on('input', '.requested-amount', function() {    
        var requested = parseInt($(this).val().replace(/,/g, ''));  // Remove commas before parsing   
        var remaining = parseInt($(this).data('remaining'));    

        // Remove any previous warning message
        $(this).closest('tr').find('.warning-message').remove();

        if (requested > remaining) {    
            alert("Requested amount cannot be greater than remaining amount.");  
            $(this).val(remaining);    

            // Append a warning message in the same row
            $(this).closest('td').append('<span class="warning-message" style="color: red; font-size: 12px;">Requested amount exceeds remaining amount</span>');
        }    
    });

    // Function to format numbers with thousand separators  
    function formatNumber(number) {  
        return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");  
    }
});

    </script>
@endpush
