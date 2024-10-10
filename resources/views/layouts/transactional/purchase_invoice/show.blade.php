@extends('adminlte::page')

@section('title', 'Purchase Invoice Details')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Purchase Invoice Details</h1>
    <form action="{{ route('purchase_invoice.destroy', $purchaseInvoice->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
        @csrf
        @method('DELETE')
        @if($purchaseInvoice->status !== 'deleted')
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


            <!-- purchase Invoice Header -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <!-- Left Side: purchase Invoice Details -->
                    <h4>Purchase Invoice</h4>
                    <dl class="row">
                        <dt class="col-sm-3">Code</dt>
                        <dd class="col-sm-9">{{ $purchaseInvoice->code }}</dd>
            
                        <dt class="col-sm-3">Order</dt>
                        <dd class="col-sm-9">{{ $purchaseInvoice->purchaseOrder->code ?? 'N/A' }}</dd>
            
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($purchaseInvoice->date)->format('Y-m-d') }}</dd>
            
                        <dt class="col-sm-3">Due Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($purchaseInvoice->due_date)->format('Y-m-d') }}</dd>
            
                        <dt class="col-sm-3">Price</dt>
                        <dd class="col-sm-9">{{ number_format($purchaseInvoice->getTotalPriceAttribute()) }}</dd>
            
                        <dt class="col-sm-3">Price Paid</dt>
                        <dd class="col-sm-9">{{ number_format($purchaseInvoice->showPriceDetails()) }}</dd>
            
                        <dt class="col-sm-3">Price Remaining</dt>
                        <dd class="col-sm-9">{{ number_format($purchaseInvoice->calculatePriceRemaining()) }}</dd>

                        
                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">{{ $purchaseInvoice->description}}</dd>
                    </dl>
                </div>
            
                <div class="col-md-6 text-right">
                    <!-- Right Side: supplier Contact Details -->
                    <h4>Supplier Contact</h4>
                    <dl class="row">
                        <dt class="col-sm-8">Supplier Name</dt>
                        <dd class="col-sm-4">{{ $purchaseInvoice->supplier->name }}</dd>
            
                        <dt class="col-sm-8">Address</dt>
                        <dd class="col-sm-4">{{ $purchaseInvoice->supplier->address }}</dd>
            
                        <dt class="col-sm-8">Phone</dt>
                        <dd class="col-sm-4">{{ $purchaseInvoice->supplier->phone }}</dd>
            
                        <dt class="col-sm-8">Email</dt>
                        <dd class="col-sm-4">{{ $purchaseInvoice->supplier->email }}</dd>
                    </dl>
                </div>
            </div>
            

            <!-- purchase Invoice Details Table -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Invoice Details</h3>
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
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseInvoice->details as $detail)
                            <tr>
                                <td>{{ $detail->product->code }}</td>
                                <td>{{ $detail->product->collection }}</td>
                                <td>{{ $detail->product->weight }} g</td>
                                <td>{{ number_format($detail->price) }}</td>
                                <td>{{ $detail->quantity }}</td>
                                <td>{{ number_format($detail->price * $detail->quantity) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-right">Total Price</th>
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
                    <form method="POST" action="{{ route('purchase_invoice.update_status', $purchaseInvoice->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending" {{ $purchaseInvoice->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ $purchaseInvoice->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                {{-- <option value="cancelled" {{ $purchaseInvoice->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option> --}}
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

                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Postings Account</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Journal</th>
                                    <th>Name</th>
                                    <th>Chart of Account</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(is_null($journal) || $postings->isEmpty() || empty($coas) || is_null($coas[0]) || is_null($coas[1]))
                                    <tr>
                                        <td colspan="6" class="text-center">No postings found for this Chart of Account.</td>
                                    </tr>
                                @else
                                @foreach($postings as $index => $obj)
                                    @if($index == 0)
                                        <tr>
                                            <td>{{ $journal->date }}</td>
                                            <td>{{ $journal->code }}</td>
                                            <td>{{ $journal->name }}</td>
                                            <td>
                                                ({{ $coas[$index]->code }}) {{ $coas[$index]->name }}
                                            </td>
                                            <td>{{ number_format(abs($obj->amount)) }}</td> 
                                            <td></td>
                                        </tr>
                                        @else
                                        <tr>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td>
                                                ({{ $coas[$index]->code }}) {{ $coas[$index]->name }}
                                            </td>
                                            <td></td>
                                            <td>{{ number_format(abs($obj->amount)) }}</td> 
                                        </tr>
                                    @endif
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            

            <!-- Edit and Back Buttons -->
            <div class="mt-3">
                <a href="{{ route('purchase_invoice.edit', $purchaseInvoice->id) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('purchase_invoice.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@stop
