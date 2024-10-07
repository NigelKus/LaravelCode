@extends('adminlte::page')

@section('title', 'Sales Invoice Details')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Sales Invoice Details</h1>
    <form action="{{ route('sales_invoice.destroy', $salesInvoice->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
        @csrf
        @method('DELETE')
    
        @can('delete', $salesInvoice)
            @if($salesInvoice->status !== 'deleted')
                <button type="submit" class="btn btn-danger">Delete</button>
            @else
                <button type="button" class="btn btn-danger" disabled>Deleted</button>
            @endif
        @else
            <button type="button" class="btn btn-danger" disabled>Not Authorized</button>
        @endcan
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
                    <h4>Sales Invoice</h4>
                    <dl class="row">
                        <dt class="col-sm-3">Code</dt>
                        <dd class="col-sm-9">{{ $salesInvoice->code }}</dd>
            
                        <dt class="col-sm-3">Order</dt>
                        <dd class="col-sm-9">{{ $salesInvoice->salesOrder->code ?? 'N/A' }}</dd>
            
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($salesInvoice->date)->format('Y-m-d') }}</dd>
            
                        <dt class="col-sm-3">Due Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($salesInvoice->due_date)->format('Y-m-d') }}</dd>
            
                        <dt class="col-sm-3">Price</dt>
                        <dd class="col-sm-9">{{ number_format($salesInvoice->getTotalPriceAttribute()) }}</dd>
            
                        <dt class="col-sm-3">Price Paid</dt>
                        <dd class="col-sm-9">{{ number_format($salesInvoice->showPriceDetails()) }}</dd>
            
                        <dt class="col-sm-3">Price Remaining</dt>
                        <dd class="col-sm-9">{{ number_format($salesInvoice->calculatePriceRemaining()) }}</dd>

                        
                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">{{ $salesInvoice->description }}</dd>
                    </dl>
                </div>
            
                <div class="col-md-6 text-right">
                    <!-- Right Side: Customer Contact Details -->
                    <h4>Customer Contact</h4>
                    <dl class="row">
                        <dt class="col-sm-8">Customer Name</dt>
                        <dd class="col-sm-4">{{ $salesInvoice->customer->name }}</dd>
            
                        <dt class="col-sm-8">Address</dt>
                        <dd class="col-sm-4">{{ $salesInvoice->customer->address }}</dd>
            
                        <dt class="col-sm-8">Phone</dt>
                        <dd class="col-sm-4">{{ $salesInvoice->customer->phone }}</dd>
            
                        <dt class="col-sm-8">Email</dt>
                        <dd class="col-sm-4">{{ $salesInvoice->customer->email }}</dd>
                    </dl>
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
                                <th>Product Code</th>
                                <th>Collection</th>
                                <th>Weight</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesInvoice->details as $detail)
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
                    <form method="POST" action="{{ route('sales_invoice.update_status', $salesInvoice->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending" {{ $salesInvoice->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ $salesInvoice->status === 'completed' ? 'selected' : '' }}>Completed</option>
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

            <div class ="card-body">
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
                                @forelse($postings as $posting)
                                    <tr>
                                        @if ($loop->first)
                                            <td>{{ $posting->journal->date }}</td>
                                            <td>{{ $posting->journal->code }}</td>
                                            <td>{{ $posting->journal->name }}</td>
                                            <td>({{ $posting->account->code }}){{ $posting->account->name }}</td>
                                        @else
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td>({{ $posting->account->code }}){{ $posting->account->name }}</td>
                                        @endif
                                        <td>{{ $posting->amount > 0 ? number_format($posting->amount) : '' }}</td>
                                        <td>{{ $posting->amount < 0 ? number_format(abs($posting->amount)) : '' }}</td>
                                        @php
                                            $previousDate = $posting->journal->date; // Keep track of the previous date
                                        @endphp
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No postings found for this Chart of Account.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        
                    </div>
                </div>
            </div>

            <!-- Edit and Back Buttons -->
            <div class="mt-3">
                <a href="{{ route('sales_invoice.edit', $salesInvoice->id) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('sales_invoice.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@stop
