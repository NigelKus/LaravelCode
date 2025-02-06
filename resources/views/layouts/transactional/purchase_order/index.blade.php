@extends('adminlte::page')

@section('title', 'Purchase Order Index')

@section('content_header')
    <h1>Purchase Orders</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Purchase Orders List</h3>
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

            <!-- Filter Form -->
            <div class="card-tools">
                <a href="{{ route('purchase_order.create') }}" class="btn btn-success btn-sm ml-2">
                    <i class="fas fa-plus"></i> Create
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('purchase_order.index') }}" class="form-inline mb-3">
                <input type="text" name="code" class="form-control ml-2" placeholder="Search by Code" value="{{ request('code') }}">
                <input type="text" name="supplier" class="form-control ml-2" placeholder="Search by Supplier" value="{{ request('supplier') }}">
                <input type="date" name="date" class="form-control ml-2" value="{{ request('date') }}">

                <select class="form-control ml-2" id="sort" name="sort" onchange="this.form.submit()">
                    <option value="">Sort by Date</option>
                    <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Recent</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                </select>

                <select class="form-control ml-2" id="status" name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>

                

                <select class="form-control ml-2" id="perPage" name="perPage" onchange="this.form.submit()">
                    <option value="10" {{ request('perPage') == '10' ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('perPage') == '25' ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('perPage') == '50' ? 'selected' : '' }}>50</option>
                </select>
                <button type="submit" class="btn btn-primary ml-2">Search</button>
            </form>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Supplier</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchaseOrders as $order)
                        <tr>
                            <td>{{ $order->code }}</td>
                            <td>{{ $order->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $order->description }}</td>
                            <td>{{ \Carbon\Carbon::parse($order->date)->format('d-m-Y') }}</td>
                            <td style="color: 
                                @if ($order->status === 'pending') orange 
                                @elseif ($order->status === 'completed') green 
                                @else black 
                                @endif;">
                                {{ ucfirst($order->status) }}
                            </td>
                            <td>
                                <a href="{{ route('purchase_order.show', $order->id) }}" class="btn btn-info btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No purchase orders found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Card -->
    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    Showing
                    {{ $purchaseOrders->firstItem() }}
                    to
                    {{ $purchaseOrders->lastItem() }}
                    of
                    {{ $purchaseOrders->total() }}
                </div>
                <div>
                    {{ $purchaseOrders->appends(request()->except('page'))->links() }}
                </div>
            </div>
        </div>
    </div>
@stop