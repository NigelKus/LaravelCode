@extends('adminlte::page')

@section('title', 'Office Details')

@section('content_header')
    <div class = "d-flex justify-content-between align-items-center">
        <h1>Office Details</h1>
            <form action="{{ route('office.destroy', $office->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
            </form>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 >Office Information</h4>
            <dl class="row mb-4">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9">{{ $office->id }}</dd>

                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $office->code }}</dd>

                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $office->name }}</dd>

                <dt class="col-sm-3">Location</dt>
                <dd class="col-sm-9">{{ $office->location }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $office->phone }}</dd>

                <dt class="col-sm-3">Opening Date</dt>
                <dd class="col-sm-9">{{ $office->opening_date }}</dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $office->created_at }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $office->updated_at }}</dd>

                <dt class="col-sm-3">Deleted At</dt>
                <dd class="col-sm-9">{{ $office->deleted_at }}</dd>
            </dl>
        </div>

        <div class ="card-body">
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Change Office Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('office.updateStatus', $office->id) }}">
                        @csrf
                        @method('POST')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ $office->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="trashed" {{ $office->status === 'trashed' ? 'selected' : '' }}>Trashed</option>
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
            <a href="{{ route('office.edit', $office->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('office.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
@stop
