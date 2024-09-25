@extends('adminlte::page')

@section('title', 'Sales Invoice Index')

@section('content_header')
    <h1>Sales Invoice</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Sales Invoice List</h3>
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
                <a href="{{ route('sales_invoice.create') }}" class="btn btn-success btn-sm ml-2">
                    <i class="fas fa-plus"></i> Create
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('sales_invoice.index') }}" class="form-inline mb-3">
                <input type="text" name="code" class="form-control ml-2" placeholder="Search by Invoice Code" value="{{ request('code') }}">
                <input type="text" name="customer" class="form-control ml-2" placeholder="Search by Customer" value="{{ request('customer') }}">
                <input type="text" name="sales_order" class="form-control ml-2" placeholder="Search by Sales Order" value="{{ request('sales_order') }}">
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
                        <th>Sales Order</th>
                        <th>Customer</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesInvoices as $salesInvoice)
                    <tr>
                        <td>{{ $salesInvoice->code }}</td>
                        <td>{{ $salesInvoice->salesOrder->code ?? 'N/A' }}</td>
                        <td>{{ $salesInvoice->customer->name ?? 'N/A' }}</td>
                        <td>{{ $salesInvoice->description }}</td>
                        <td>{{ $salesInvoice->status }}</td>
                        <td><a href="{{ route('sales_invoice.show', $salesInvoice->id) }}" class="btn btn-info btn-sm">View</a></td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No Sales Invoice found.</td>
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
                    {{ $salesInvoices->firstItem() }}
                    to
                    {{ $salesInvoices->lastItem() }}
                    of
                    {{ $salesInvoices->total() }}
                </div>
                <div>
                    {{ $salesInvoices->appends(request()->except('page'))->links() }}
                </div>
            </div>
        </div>
    </div>
@stop
