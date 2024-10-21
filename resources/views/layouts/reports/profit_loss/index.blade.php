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

            <form method="POST" action="/admin/reports/profit_loss/generate" class="form-horizontal">
                @csrf
                <div class="form-group">
                    <label for="from_date">From Date</label> 
                    <input type="datetime-local" class="form-control @error('from_date') is-invalid @enderror" id="from_date" name="from_date" value="{{ old('from_date', \Carbon\Carbon::now()->addHours(7)->format('Y-m-d\TH:i')) }}">
                    @error('from_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="to_date">To Date</label> 
                    <input type="datetime-local" class="form-control @error('to_date') is-invalid @enderror" id="to_date" name="to_date" value="{{ old('to_date', \Carbon\Carbon::now()->addHours(7)->format('Y-m-d\TH:i')) }}">
                    @error('to_date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary">Generate</button>
            </form>
        </div>
    </div>
@stop

