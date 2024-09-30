@extends('adminlte::page')

@section('title', 'Customer Details')

@section('content_header')
    <div class = "d-flex justify-content-between align-items-center">
        <h1>Customer Details</h1>
            <form action="{{ route('customer.destroy', $customer->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
            </form>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 >Customer Information</h4>
            <dl class="row mb-4">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9">{{ $customer->id }}</dd>

                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $customer->code }}</dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $customer->name }}</dd>

                <dt class="col-sm-3">Sales Category</dt>
                <dd class="col-sm-9">{{ $customer->sales_category }}</dd>

                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $customer->address }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $customer->phone }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $customer->description }}</dd>

                <dt class="col-sm-3">Birth Date</dt>
                <dd class="col-sm-9">{{ $customer->birth_date }}</dd>

                <dt class="col-sm-3">Birth City</dt>
                <dd class="col-sm-9">{{ $customer->birth_city }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $customer->email }}</dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $customer->created_at }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $customer->updated_at }}</dd>

                <dt class="col-sm-3">Deleted At</dt>
                <dd class="col-sm-9">{{ $customer->deleted_at }}</dd>
            </dl>
        </div>

        <div class ="card-body">
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Change Customer Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('customer.updateStatus', $customer->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ $customer->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="trashed" {{ $customer->status === 'trashed' ? 'selected' : '' }}>Trashed</option>
                                {{-- <option value="cancelled" {{ $salesOrder->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option> --}}
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

        <div class="card-footer">
            <a href="{{ route('customer.edit', $customer->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('customer.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
@stop
