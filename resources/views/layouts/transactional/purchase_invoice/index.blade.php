@extends('adminlte::page')

@section('title', 'Purchase Invoice Index')

@section('content_header')
    <h1>Purchase Invoice</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Purchase Invoices List</h3>
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
            <div class="card-tools">
                <!-- Create Button with Icon -->
                <a href="{{ route('purchase_invoice.create') }}" class="btn btn-success btn-sm ml-2">
                    <i class="fas fa-plus"></i> Create
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('purchase_invoice.index') }}" class="form-inline mb-3">
                <input type="text" name="code" class="form-control ml-2" placeholder="Search by Invoice Code" value="{{ request('code') }}">
                <input type="text" name="supplier" class="form-control ml-2" placeholder="Search by Supplier" value="{{ request('supplier') }}">
                <input type="text" name="purchase_order" class="form-control ml-2" placeholder="Search by Purchase Order" value="{{ request('purchase_order') }}">
                <input type="date" name="date" class="form-control ml-2" value="{{ request('date') }}">
                

                <select class="form-control ml-2" id="status" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>

                <select class="form-control ml-2" id="sort" name="sort" onchange="this.form.submit()">
                    <option value="">Sort by Date</option>
                    <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Recent</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                </select>

                <select class="form-control ml-2" id="perPage" name="perPage" onchange="this.form.submit()">
                    <option value="10" {{ request('perPage') == '10' ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('perPage') == '25' ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('perPage') == '50' ? 'selected' : '' }}>50</option>
                </select>
                <button type="submit" class="btn btn-primary ml-2">Search</button>
            </form>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Purchase Order</th>
                        <th>Supplier</th>
                        <th>Description</th>
                        <th>Total Price</th>
                        <th>Paid</th>
                        <th>Price Remaining</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseInvoices as $purchaseInvoice)
                    <tr>
                        <td>{{ $purchaseInvoice->code }}</td>
                        <td>{{ $purchaseInvoice->purchaseOrder->code ?? 'N/A' }}</td>
                        <td>{{ $purchaseInvoice->supplier->name ?? 'N/A' }}</td>
                        <td>{{ $purchaseInvoice->description }}</td>
                        <td style="text-align: right;">{{ number_format($purchaseInvoice->total_price) }}</td> <!-- Total Price with thousand separator -->
                        <td style="text-align: right;">{{ number_format($purchaseInvoice->total_price - $purchaseInvoice->calculatePriceRemaining()) }}</td> <!-- Paid with thousand separator -->
                        <td style="text-align: right;">{{ number_format($purchaseInvoice->calculatePriceRemaining()) }}</td>
                        <td style="color: 
                            @if ($purchaseInvoice->status === 'pending') orange 
                            @elseif ($purchaseInvoice->status === 'completed') green 
                            @else black 
                            @endif;">
                            {{ ucfirst($purchaseInvoice->status) }}
                        </td>

                        <td>
                            <a href="{{ route('purchase_invoice.show', $purchaseInvoice->id) }}" class="btn btn-info btn-sm">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">No purchase Invoice found.</td>
                    </tr>
                    @endforelse
                </tbody>
                
            </table>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Showing
                    {{ $purchaseInvoices->firstItem() }}
                    to
                    {{ $purchaseInvoices->lastItem() }}
                    of
                    {{ $purchaseInvoices->total() }}
                </div>
                <div>
                    {{ $purchaseInvoices->appends(request()->except('page'))->links() }}
                </div>
            </div>
        </div>
    </div>
@stop