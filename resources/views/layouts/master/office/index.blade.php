@extends('adminlte::page')

@section('title', 'Office Index')

@section('content_header')
    <h1>Office</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Office List</h3>
        <div class="card-tools">
            <a href="{{ route('office.create') }}" class="btn btn-success btn-sm ml-2">
                <i class="fas fa-plus"></i> Create
            </a>
        </div>
        
    
        <!-- /.card-tools -->
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Code</th> <!-- Add header for the 'code' column -->
                        <th>Name</th>
                        <th>Location</th>
                        <th>
                            <!-- Dropdown for Status Filter -->
                            <form method="GET" action="{{ route('office.index') }}" style="display:inline;">
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
                        @foreach($offices as $office)
                            <tr>
                                <td>{{ $office->code }}</td> 
                                <td>{{ $office->name }}</td>
                                <td>{{ $office->location }}</td>
                                <td>{{ $office->status }}</td>
                                <td>
                                    <a href="{{ route('office.show', $office->id) }}" class="btn btn-info btn-sm">View</a>
                                </td>
                            </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
