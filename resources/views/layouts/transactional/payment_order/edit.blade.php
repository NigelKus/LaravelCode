@extends('adminlte::page')

@section('title', 'Edit Payment Sales')

@section('content_header')
    <h1>Edit Payment Sales</h1>
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

            <form method="POST" action="/admin/transactional/payment_order/update" class="form-horizontal">
                @csrf
                @method('PUT')
            <div class="form-group">
                <label for="payment_order_id">Payment Sales Code</label>
                <input type="hidden" name="payment_order_id" value="{{ $payment_order_id }}">
                    <input type="text" class="form-control @error('payment_order_id') is-invalid @enderror" 
                        id="payment_order_id_display" 
                        value="{{ $payment_order_code }}" 
                        readonly>
                    @error('payment_order_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <input type="hidden" name="customer_id" value="{{ old('customer_id', $paymentOrder->customer_id) }}">
                    <input type="text" class="form-control @error('customer_id') is-invalid @enderror" 
                            id="customer_id_display" 
                            value="{{ $customers->firstWhere('id', old('customer_id', $paymentOrder->customer_id))->name ?? 'Customer Not Found' }}" 
                            readonly>
                    @error('customer_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="payment_type">Payment Type</label>
                    <select class="form-control @error('payment_type') is-invalid @enderror" id="payment_type" name="payment_type" required>
                        <option value="">Select a Payment Type</option>
                        <option value="kas" {{ old('payment_type', $payment_type) == 'kas' ? 'selected' : '' }}>Kas</option>
                        <option value="bank" {{ old('payment_type', $payment_type) == 'bank' ? 'selected' : '' }}>Bank</option>
                    </select>
                    @error('payment_type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Description Field -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $paymentOrder->description) }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Payment Date Field -->
                <div class="form-group">
                    <label for="date">Payment Date</label>
                    <input type="datetime-local" class="form-control @error('date') is-invalid @enderror" id="date" name="date" 
                        value="{{ old('date', $paymentOrder->date ? \Carbon\Carbon::parse($paymentOrder->date)->format('Y-m-d\TH:i') : '') }}">
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
                                @foreach ($combinedDetails as $detail)
                                    @include('layouts.transactional.payment_order.partials.invoice_line_edit', [
                                        'invoice_id' => $detail['invoice_id'],
                                        'invoice_code' => $detail['invoice_code'],
                                        'requested' => $detail['requested'],
                                        'original_price' =>  $detail['original_price'],
                                        'remaining_price' => $detail['remaining_price'],
                                    ])
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Hidden Invoice Line Template -->
                        <table style="display: none;" id="invoice-line-template">
                            <tbody>
                                @include('layouts.transactional.payment_order.partials.invoice_line_edit', [
                                    'invoice_id' => '',
                                    'invoice_code' => '',
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
                    <a href="{{ route('payment_order.show', $paymentOrder->id) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@stop

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
$(document).ready(function() {
    // Set the sidebar height to match the document height
    function adjustSidebarHeight() {
        $('.main-sidebar').height($(document).outerHeight());
    }

    adjustSidebarHeight();

    $(window).resize(function() {
        adjustSidebarHeight();
    });

    // Event listener for requested input field
    $('#invoice-sales-table').on('input', '.payment', function() {
        // Get the current requested amount and remaining price
        var requestedAmount = parseFloat($(this).val()) || 0; // Default to 0 if NaN
        var remainingPrice = parseFloat($(this).closest('tr').find('.remaining_price').val().replace(/,/g, '')) || 0; // Default to 0 if NaN

        $(this).closest('tr').find('.warning-message').remove();
        // Check if the requested amount exceeds the remaining price
        if (requestedAmount > remainingPrice) {
            alert('Requested amount cannot exceed the remaining price.');
            $(this).val(remainingPrice); // Set requested to remaining price
            $(this).closest('td').append('<span class="warning-message" style="color: red; font-size: 12px;">Requested amount exceeds remaining amount</span>');
        
        } else if (requestedAmount < 0) {
            alert('Requested amount cannot be negative.');
            $(this).val(0); // Reset to 0 if negative
        }
    });

    $('#invoice-sales-table').on('click', '.del-invoice-line', function() {
        $(this).closest('tr').remove();       
    });
});

</script>