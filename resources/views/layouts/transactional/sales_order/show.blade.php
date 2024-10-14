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
                        
                <div class="row mb-4">  
                    <div class="col-md-6">  
                    <!-- Left Side: Sales Order Details -->  
                    <h4>Sales Order</h4>  
                        <dl class="row">  
                            <dt class="col-sm-3">ID</dt>  
                            <dd class="col-sm-9">{{ $salesOrder->id }}</dd>  
                            <dt class="col-sm-3">Code</dt>  
                            <dd class="col-sm-9">{{ $salesOrder->code }}</dd>  
                            <dt class="col-sm-3">Date</dt>  
                            <dd class="col-sm-9">{{ \Carbon\Carbon::parse($salesOrder->date)->format('Y-m-d H:i') }}</dd>
                            <dt class="col-sm-3">Description</dt>  
                            <dd class="col-sm-9">{{ $salesOrder->description }}</dd>  
                        </dl>  
                    </div>  
                    <div class="col-md-6 text-right">  
                    <!-- Right Side: Customer Contact Details -->  
                        <h4>Customer Contact</h4>  
                        <dl class="row">
                            <dt class="col-sm-8">Customer Name</dt>  
                            <dd class="col-sm-4">{{ $salesOrder->customer->name }}</dd>    
                            <dt class="col-sm-8 ">Address</dt>  
                            <dd class="col-sm-4 ">{{ $salesOrder->customer->address }}</dd>  
                            <dt class="col-sm-8 ">Phone</dt>  
                            <dd class="col-sm-4 ">{{ $salesOrder->customer->phone }}</dd>  
                            <dt class="col-sm-8 ">Email</dt>  
                            <dd class="col-sm-4 ">{{ $salesOrder->customer->email }}</dd>  
                        </dl>  
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
                        @if (!$deleted)
                            <button type="submit" class="btn btn-primary">Update Status</button>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Edit Button -->
            <div class="mt-3">
                @if (!$deleted)
                    <a href="{{ route('sales_order.edit', $salesOrder->id) }}" class="btn btn-warning">Edit</a>
                @endif
                <a href="{{ route('sales_order.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@stop
