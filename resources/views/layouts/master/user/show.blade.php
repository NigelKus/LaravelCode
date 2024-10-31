@extends('adminlte::page')

@section('title', 'User Details')

@section('content_header')
    <div class = "d-flex justify-content-between align-items-center">
        <h1>User Details</h1>
            <form action="{{ route('user.destroy', $user->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
            </form>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 >User Information</h4>
            <dl class="row mb-4">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $user->id }}</dd>

                <dt class="col-sm-3">Role</dt>
                <dd class="col-sm-9">{{ $user->role }}</dd>

                <dt class="col-sm-3">Office</dt>
                <dd class="col-sm-9">{{ $user->office->name }}</dd>

                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>

                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $user->phone }}</dd>

                <dt class="col-sm-3">Birth Date</dt>
                <dd class="col-sm-9">{{ $user->birth_date }}</dd>
                
                <dt class="col-sm-3">Birth Location</dt>
                <dd class="col-sm-9">{{ $user->birth_date }}</dd>
                
                <dt class="col-sm-3">Address</dt>
                <dd class="col-sm-9">{{ $user->address }}</dd>

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $user->created_at }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $user->updated_at }}</dd>

                <dt class="col-sm-3">Deleted At</dt>
                <dd class="col-sm-9">{{ $user->deleted_at }}</dd>
            </dl>
        </div>

        <div class ="card-body">
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Change User Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('user.updateStatus', $user->id) }}">
                        @csrf
                        @method('POST')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="trashed" {{ $user->status === 'trashed' ? 'selected' : '' }}>Trashed</option>
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
            <a href="{{ route('user.edit', $user->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('user.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
@stop
