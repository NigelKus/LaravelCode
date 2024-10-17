@extends('adminlte::page')

@section('title', 'Profit Loss Index')

@section('content_header')
    <h1>Profit Loss</h1>
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

            {{-- <form method="POST" action="/admin/reports/general_ledger/generate" class="form-horizontal">
                @csrf --}}
                <div class="form-group">
                    <label for="debit_id">Debit</label>
                    <select class="form-control @error('debit_id') is-invalid @enderror" id="debit_id" name="debit_id">
                        <option value="">Select a Chart of Account</option>
                        @foreach($CoAs as $coa) 
                            <option value="{{ $coa->id }}" {{ old('id') == $coa->id ? 'selected' : '' }}>
                                {{ $coa->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('debit_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="kredit_id">Kredit</label>
                    <select class="form-control @error('kredit_id') is-invalid @enderror" id="kredit_id" name="kredit_id">
                        <option value="">Select a Chart of Account</option>
                        @foreach($CoAs as $coa) 
                            <option value="{{ $coa->id }}" {{ old('id') == $coa->id ? 'selected' : '' }}>
                                {{ $coa->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('kredit_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="from_date">Date</label> 
                    <input type="datetime-local" class="form-control @error('from_date') is-invalid @enderror" id="from_date" name="from_date" value="{{ old('from_date', \Carbon\Carbon::now()->addHours(7)->format('Y-m-d\TH:i')) }}">
                    @error('from_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="amount">Amount</label>
                    <input type="text" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" required>
                    @error('amount')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description') }}" >
                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>


                <button type="submit" class="btn btn-primary">Generate</button>
            </form>
        </div>
    </div>
@stop

