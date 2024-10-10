@extends('adminlte::page')

@section('title', 'Payment Sales Details')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Payment Sales Details</h1>
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
                    <h4>Payment Sales</h4>


                    <dl class="row">
                        <dt class="col-sm-3">Code</dt>
                        <dd class="col-sm-9">{{ $paymentOrder->code }}</dd>

                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($paymentOrder->date)->format('Y-m-d') }}</dd>

                        
                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">{{ $paymentOrder->description }}</dd>
                    </dl>
                </div>
                <div class="col-md-6 text-md-right">
                    <!-- Right Side: Customer Contact Details -->
                    <h4>Customer Contact</h4>
                    <dl class="row">
                        <dt class="col-sm-8">Customer Name</dt>
                        <dd class="col-sm-4">{{ $paymentOrder->customer->name }}</dd>
            
                        <dt class="col-sm-8">Address</dt>
                        <dd class="col-sm-4">{{ $paymentOrder->customer->address }}</dd>

                        <dt class="col-sm-8">Phone</dt>
                        <dd class="col-sm-4">{{ $paymentOrder->customer->phone }}</dd>

                        <dt class="col-sm-8">Email</dt>
                        <dd class="col-sm-4">{{ $paymentOrder->customer->email }}</dd>
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
                                <th>Invoice Code</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentOrder->paymentDetails as $detail)
                                <tr>
                                    <td>{{ $detail->salesInvoice->code }}</td>
                                    <td>{{ number_format($detail->price) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total Price</th>
                                <th>{{ number_format($totalPrice) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
                

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
            
    
        <div class="mt-3">
            <a href="{{ route('payment_order.edit', $paymentOrder->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('payment_order.index') }}" class="btn btn-secondary">Back to List</a>
        </div> 
@stop