@extends('adminlte::page')

@section('title', 'Purchase Order Details')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>Purchase Order Details</h1>
        <form action="{{ route('purchase_order.destroy', $purchaseOrder->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
            @csrf
            @method('DELETE')
            @if($purchaseOrder->status !== 'deleted')
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
                    <h4>Purchases Order</h4>  
                        <dl class="row">  
                            <dt class="col-sm-3">ID</dt>  
                            <dd class="col-sm-9">{{ $purchaseOrder->id }}</dd>  
                            <dt class="col-sm-3">Code</dt>  
                            <dd class="col-sm-9">{{ $purchaseOrder->code }}</dd>   
                            <dt class="col-sm-3">Date</dt>  
                            <dd class="col-sm-9">{{ \Carbon\Carbon::parse($purchaseOrder->date)->format('Y-m-d') }}</dd>  
                            <dt class="col-sm-3">Description</dt>  
                            <dd class="col-sm-9">{{ $purchaseOrder->Description }}</dd> 
                        </dl>  
                    </div>  
                    <div class="col-md-6 text-right">  
                    <!-- Right Side: supplier Contact Details -->  
                        <h4>Supplier Contact</h4>  
                        <dl class="row">  
                            <dt class="col-sm-8">Supplier Name</dt>  
                            <dd class="col-sm-4">{{ $purchaseOrder->supplier->name }}</dd> 
                            <dt class="col-sm-8 ">Address</dt>  
                            <dd class="col-sm-4 ">{{ $purchaseOrder->supplier->address }}</dd>  
                            <dt class="col-sm-8 ">Phone</dt>  
                            <dd class="col-sm-4 ">{{ $purchaseOrder->supplier->phone }}</dd>  
                            <dt class="col-sm-8 ">Email</dt>  
                            <dd class="col-sm-4 ">{{ $purchaseOrder->supplier->email }}</dd>  
                        </dl>  
                    </div>  
                </div>
            
            <!-- Sales Order Details Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Purchase Details</h3>
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
                            @foreach($purchaseOrder->details as $detail)
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
                    <form method="POST" action="{{ route('purchase_order.update_status', $purchaseOrder->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending" {{ $purchaseOrder->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ $purchaseOrder->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                {{-- <option value="cancelled" {{ $purchaseOrder->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option> --}}
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
                <a href="{{ route('purchase_order.edit', $purchaseOrder->id) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('purchase_order.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@stop
