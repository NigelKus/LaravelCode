@extends('adminlte::page')

@section('title', 'Payment Order Details')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Payment Order Details</h1>
    <form action="{{ route('payment_order.destroy', $paymentOrder->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
        @csrf
        @method('DELETE')
        @if($paymentOrder->status !== 'deleted')
        <button type="submit" class="btn btn-danger">Delete</button>
    @else
        <button type="button" class="btn btn-danger" disabled>Deleted</button>
    @endif
    </form>
</div>
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
            <!-- Sales Invoice Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <!-- Left Side: Sales Invoice Details -->
                    <h4>Payment Order</h4>
                    <p><strong>Code:</strong> {{ $paymentOrder->code }}</p>
                    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($paymentOrder->date)->format('Y-m-d') }}</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <!-- Right Side: Customer Contact Details -->
                    <h4>Customer Contact</h4>
                    <p><strong>Customer Name:</strong> {{ $paymentOrder->customer->name }}</p>
                    <p><strong>Address:</strong> {{ $paymentOrder->customer->address }}</p>
                    <p><strong>Phone:</strong> {{ $paymentOrder->customer->phone }}</p>
                    <p><strong>Email:</strong> {{ $paymentOrder->customer->email }}</p>
                </div>
            </div>

            <!-- Sales Invoice Details Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Invoice Details</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Invoice Code</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentOrder->paymentDetails as $detail)
                                <tr>
                                    <td>{{ $detail->salesInvoice->code }}</td>
                                    <td>{{ number_format($detail->price, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Price</th>
                                <th>{{ number_format($totalPrice, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                

            <!-- Status Update Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Change Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payment_order.update_status', $paymentOrder->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending" {{ $paymentOrder->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ $paymentOrder->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                {{-- <option value="cancelled" {{ $salesInvoice->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option> --}}
                            </select>
                            @error('status')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </form>
                </div>
            </div>

            <!-- Edit and Back Buttons -->
            <div class="mt-3">
                <a href="{{ route('payment_order.edit', $paymentOrder->id) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('payment_order.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@stop