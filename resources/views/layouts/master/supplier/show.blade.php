@extends('adminlte::page')

@section('title', 'Supplier Details')

@section('content_header')
    <h1>Supplier Details</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Supplier Information</h3>
            <div class="card-tools">
                <form action="{{ route('supplier.destroy', $supplier->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9">{{ $supplier->id }}</dd>

                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $supplier->code }}</dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $supplier->name }}</dd>

                <dt class="col-sm-3">Sales Category</dt>
                <dd class="col-sm-9">{{ $supplier->supplier_category }}</dd>

                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $supplier->address }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $supplier->phone }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $supplier->description }}</dd>

                <dt class="col-sm-3">Birth Date</dt>
                <dd class="col-sm-9">{{ $supplier->birth_date }}</dd>

                <dt class="col-sm-3">Birth City</dt>
                <dd class="col-sm-9">{{ $supplier->birth_city }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $supplier->email }}</dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $supplier->created_at }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $supplier->updated_at }}</dd>

                <dt class="col-sm-3">Deleted At</dt>
                <dd class="col-sm-9">{{ $supplier->deleted_at }}</dd>
            </dl>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Change Supplier Status</h3>
            </div>

            <div class="card-body">
                <form action="{{ route('supplier.updateStatus', $supplier->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to change the status?');">
                    @csrf
                    @method('POST')

                    <div class="form-group">
                        <select class="form-control" id="status" name="status" required>
                            <option value="active" {{ $supplier->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="trashed" {{ $supplier->status == 'trashed' ? 'selected' : '' }}>Trashed</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>

        <div class="card-footer">
            <a href="{{ route('supplier.edit', $supplier->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('supplier.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
@stop
