@extends('adminlte::page')

@section('title', 'Sales Order Details')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Sales Order Details</h1>
        <form action="{{ route('sales_order.destroy', $salesOrder->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
            @csrf
            @method('DELETE')
            @if($salesOrder->status !== 'deleted')
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
            <!-- Sales Order Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <!-- Left Side: Sales Order Details -->
                    <h4>Sales Order</h4>
                    <p><strong>Code:</strong> {{ $salesOrder->code }}</p>
                    <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($salesOrder->date)->format('Y-m-d') }}</p>
                    <p><strong>Customer Name:</strong> {{ $salesOrder->customer->name }}</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <!-- Right Side: Customer Contact Details -->
                    <h4>Customer Contact</h4>
                    <p><strong>Address:</strong> {{ $salesOrder->customer->address }}</p>
                    <p><strong>Phone:</strong> {{ $salesOrder->customer->phone }}</p>
                    <p><strong>Email:</strong> {{ $salesOrder->customer->email }}</p>
                </div>
            </div>

            <!-- Sales Order Details Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Order Details</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product Code</th>
                                <th>Collection</th>
                                <th>Weight</th>
                                {{--     --}}
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Remaining Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesOrder->details as $detail)
                                <tr>
                                    <td>{{ $detail->product->code }}</td>
                                    <td>{{ $detail->product->collection }}</td>
                                    <td>{{ $detail->product->weight }} g</td>
                                    {{-- <td>{{ $detail->status}}</td> --}}
                                    <td>{{ number_format($detail->price, 2) }}</td>
                                    <td>{{ $detail->quantity }}</td>
                                    <td>{{ $detail->quantity_remaining }}</td>
                                    <td>{{ number_format($detail->price * $detail->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-right">Total Price</th>
                                <th>{{ number_format($totalPrice, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Status Update Card -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Change Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('sales_order.update_status', $salesOrder->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending" {{ $salesOrder->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ $salesOrder->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                {{-- <option value="cancelled" {{ $salesOrder->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option> --}}
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

            <!-- Edit Button -->
            <div class="mt-3">
                <a href="{{ route('sales_order.edit', $salesOrder->id) }}" class="btn btn-warning">Edit</a>


                <a href="{{ route('sales_order.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@stop
