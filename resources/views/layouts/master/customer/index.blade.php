@extends('adminlte::page')

@section('title', 'Customer Index')

@section('content_header')
    <h1>Customer</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Customer List</h3>
        <div class="card-tools">
            <!-- Buttons, labels, and many other things can be placed here! -->
            <!-- Here is a label for example -->
        
            <!-- Create Button with Icon -->
            <a href="{{ route('customer.create') }}" class="btn btn-success btn-sm ml-2">
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
                        <th>Sales Category</th>
                        <th>Email</th>
                        <th>
                            <!-- Dropdown for Status Filter -->
                            <form method="GET" action="{{ route('customer.index') }}" style="display:inline;">
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
                    @foreach($customers as $customer)
                    <tr>
                        <td>{{ $customer->code }}</td> 
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->sales_category }}</td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->status }}</td>
                        <td>
                            <a href="{{ route('customer.show', $customer->id) }}" class="btn btn-info btn-sm">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- /.card-body -->
        {{-- <div class="card-footer">
        AdminLTE
        </div> --}}
        <!-- /.card-footer -->
    </div>
    <!-- /.card -->
@endsection
