@extends('adminlte::page')

@section('title', 'Chart of Account Details')

@section('content_header')
    <div class = "d-flex justify-content-between align-items-center">
        <h1>Chart of Account Details</h1>
            <form action="{{ route('CoA.destroy', $CoA->id) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
            </form>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 >Chart of Account Information</h4>
            <dl class="row mb-4">
                <dt class="col-sm-2">ID</dt>
                <dd class="col-sm-9">{{ $CoA->id }}</dd>

                <dt class="col-sm-2">Code</dt>
                <dd class="col-sm-9">{{ $CoA->code }}</dd>

                <dt class="col-sm-2">Name</dt>
                <dd class="col-sm-9">{{ $CoA->name }}</dd>

                <dt class="col-sm-2">Description</dt>
                <dd class="col-sm-9">{{ $CoA->description }}</dd>

                <dt class="col-sm-2">Created At</dt>
                <dd class="col-sm-9">{{ $CoA->created_at }}</dd>

                <dt class="col-sm-2">Updated At</dt>
                <dd class="col-sm-9">{{ $CoA->updated_at }}</dd>

                <dt class="col-sm-2">Deleted At</dt>
                <dd class="col-sm-9">{{ $CoA->deleted_at }}</dd>
            </dl>
        </div>

        <div class ="card-body">
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Change Chart of Account Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('CoA.updateStatus', $CoA->id) }}">
                        @csrf
                        @method('POST')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ $CoA->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="trashed" {{ $CoA->status === 'trashed' ? 'selected' : '' }}>Trashed</option>
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

        <div class ="card-body">
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Journal Account</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Price</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($CoA->postings as $posting)
                                <tr>
                                    <td>{{ $posting->journal->code }}</td>
                                    <td>{{ $posting->journal->name }}</td>
                                    <td>{{ $posting->journal->date }}</td>
                                    <td>{{ $posting->amount }}</td>
                                    <td>{{ $posting->journal->description }}</td>
                                    <td>
                                        <a href="{{ route('CoA.show', $CoA->id) }}" class="btn btn-info btn-sm">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">No postings found for this Chart of Account.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <a href="{{ route('CoA.edit', $CoA->id) }}" class="btn btn-warning">Edit</a>
            <a href="{{ route('CoA.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
@stop
