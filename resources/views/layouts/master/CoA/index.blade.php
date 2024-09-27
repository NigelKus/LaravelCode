@extends('adminlte::page')

@section('title', 'Chart of Account Index')

@section('content_header')
    <h1>Chart of Account</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Chart of Accounts List</h3>
        <div class="card-tools">
            <a href="{{ route('CoA.create') }}" class="btn btn-success btn-sm ml-2">
                <i class="fas fa-plus"></i> Create
            </a>
        </div>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>
                        <!-- Dropdown for Status Filter -->
                        <form method="GET" action="{{ route('CoA.index') }}" style="display:inline;">
                            <select class="form-control" id="status-filter" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="trashed" {{ request('status') == 'trashed' ? 'selected' : '' }}>Trashed</option>
                            </select>
                        </form>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($CoAs as $CoA)
                    <tr>
                        <td>{{ $CoA->code }}</td> 
                        <td>{{ $CoA->name }}</td>
                        <td>{{ $CoA->status }}</td>
                        <td>
                            <a href="{{ route('CoA.show', $CoA->id) }}" class="btn btn-info btn-sm">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No Chart of Account found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

