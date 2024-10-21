@extends('adminlte::page')

@section('title', 'Journal Voucher Details')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1>Journal Voucher Details</h1>
    <form action="{{ route('journal.destroy', $journalVoucher->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
</div>
@stop
@section('content')
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="row mb-4">
                <div class="col-md-6">
                    <h4>Journal Voucher</h4>
                    <br>
                    <dl class="row">
                        <dt class="col-sm-3">ID</dt>
                        <dd class="col-sm-9">{{ $journalVoucher->id }}</dd>
                        
                        <dt class="col-sm-3">Code</dt>
                        <dd class="col-sm-9">{{ $journalVoucher->code}}</dd>
            
                        <dt class="col-sm-3">Date</dt>
                        <dd class="col-sm-9">{{ \Carbon\Carbon::parse($journalVoucher->date)->format('Y-m-d H:i') }}</dd>
            
                        <dt class="col-sm-3">Name</dt>
                        <dd class="col-sm-9">{{$journalVoucher->name }}</dd>
                        
                        <dt class="col-sm-3">Description</dt>
                        <dd class="col-sm-9">{{ $journalVoucher->description}}</dd>
                    </dl>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Change Status</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('journal.update_status', $journalVoucher->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending" {{ $journalVoucher->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="completed" {{ $journalVoucher->status === 'completed' ? 'selected' : '' }}>Completed</option>
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

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Journal Voucher Detail</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Journal Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Chart of Account</th>
                                <th>Debit</th>
                                <th>Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($details->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center">No postings found for this Chart of Account.</td>
                                </tr>
                            @else
                                @foreach($details as $index => $obj)
                                    <tr>
                                        @if($index == 0)
                                            <td>{{ \Carbon\Carbon::parse($journalVoucher->date)->format('Y-m-d') }}</td>
                                            <td>{{ $journal->code }}</td>
                                            <td>{{ $journal->name}}</td>
                                            <td>{{ $obj->description }}</td>
                                            <td> ({{ $obj->account->code }}) {{ $obj->account->name }} </td>
                                            <td>{{ $obj->amount }}</td> 
                                            <td></td>
                                        @else
                                            @if($obj->amount > 0)
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>{{ $obj->description }}</td>
                                                <td>({{ $obj->account->code }}) {{ $obj->account->name }}</td>
                                                <td>{{ number_format(abs($obj->amount)) }}</td>
                                                <td></td>
                                            @else
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td>{{ $obj->description }}</td>
                                                <td>({{ $obj->account->code ?? 0}}) {{ $obj->account->name ?? 0}}</td>
                                                <td></td>
                                                <td>{{ number_format(abs($obj->amount)) }}</td>
                                            @endif
                                        @endif
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-3">
                <a href="{{ route('journal.edit', $journalVoucher->id) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('journal.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        
        </div>
    </div>
@stop