@extends('adminlte::page')

@section('title', 'Journal Voucher Index')

@section('content_header')
    <h1>Journal Voucher</h1>
@stop

@section('content')

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Journal List</h2>


            <div class="card-tools">
                <a href="{{ route('journal.create') }}" class="btn btn-success btn-sm ml-2">
                    <i class="fas fa-plus"></i> Create
                </a>
            </div>
        </div>
        


        <div class="card-body">
            <form method="GET" action="{{ route('journal.index') }}" class="form-inline mb-3">
                <input type="text" name="code" class="form-control ml-2" placeholder="Search by Code" value="{{ request('code') }}">
                <input type="text" name="name" class="form-control ml-2" placeholder="Search by name" value="{{ request('name') }}">
                <input type="date" name="date" class="form-control ml-2" value="{{ request('date') }}">
                <select class="form-control ml-2" id="sort" name="sort">
                    <option value="">Sort by Date</option>
                    <option value="recent" {{ request('sort') == 'recent' ? 'selected' : '' }}>Recent</option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest</option>
                </select>
                <select class="form-control ml-2" id="status" name="status">
                    <option value="">All Statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
                <select class="form-control ml-2" id="perPage" name="perPage">
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
                        <th>Name</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($journalVouchers as $voucher)
                        <tr>
                            <td>{{ $voucher->code }}</td>               
                            <td>{{ $voucher->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($voucher->date)->format('Y-m-d') }}</td>
                            <td>{{ ucfirst($voucher->status) }}</td>
                            <td>
                                <a href="{{ route('journal.show', $voucher->id) }}" class="btn btn-info btn-sm">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">No Journal Voucher found.</td>
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
                    {{ $journalVouchers->firstItem() }}
                    to
                    {{ $journalVouchers->lastItem() }}
                    of
                    {{ $journalVouchers->total() }}
                </div>
                <div>
                    {{ $journalVouchers->appends(request()->except('page'))->links() }}
                </div>
            </div>
        </div>
    </div>



    </div>
@stop

