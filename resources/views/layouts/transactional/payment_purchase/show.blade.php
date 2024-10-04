@extends('adminlte::page')

@section('title', 'Payment Purchase Details')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Payment Purchase Details</h1>
    <form action="{{ route('payment_purchase.destroy', $paymentPurchase->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
        @csrf
        @method('DELETE')
        @if($paymentPurchase->status !== 'deleted')
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
                    <h4>Payment Purchase</h4>

                    <dl class="row">
                        <dt class="col-sm-3">Code</dt>
                        <dd class="col-sm-9">{{ $paymentPurchase->code }}</dd>
            
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($paymentPurchase->date)->format('Y-m-d') }}</dd>
                    </dl>
                </div>
                <div class="col-md-6 text-md-right">
                    <!-- Right Side: supplier Contact Details -->
                    <h4>Supplier Contact</h4>
                    <dl class="row">
                        <dt class="col-sm-8">Supplier Name</dt>
                        <dd class="col-sm-4">{{ $paymentPurchase->supplier->name }}</dd>
            
                        <dt class="col-sm-8">Address</dt>
                        <dd class="col-sm-4">{{ $paymentPurchase->supplier->address }}</dd>

                        <dt class="col-sm-8">Phone</dt>
                        <dd class="col-sm-4">{{ $paymentPurchase->supplier->phone }}</dd>

                        <dt class="col-sm-8">Email</dt>
                        <dd class="col-sm-4">{{ $paymentPurchase->supplier->email }}</dd>
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
                            @foreach($paymentPurchase->paymentDetails as $detail)
                                <tr>
                                    <td>{{ $detail->purchaseInvoice->code }}</td>
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
                

            <!-- Status Update Card -->
        <div class ="card-body">
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Change Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payment_purchase.update_status', $paymentPurchase->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending" {{ $paymentPurchase->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ $paymentPurchase->status === 'completed' ? 'selected' : '' }}>Completed</option>
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
                                        <td>{{ $posting->amount < 0 ? '-' . number_format(abs($posting->amount)) : '' }}</td>
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

            <div class="card-footer">
                <a href="{{ route('payment_purchase.edit', $paymentPurchase->id) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('payment_purchase.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@stop