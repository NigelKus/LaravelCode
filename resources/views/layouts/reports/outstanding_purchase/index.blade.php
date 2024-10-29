@extends('adminlte::page')

@section('title', 'Outstanding Purchase Index')

@section('content_header')
    <h1>Outstanding Purchase</h1>
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

            <form method="POST" action="/admin/reports/outstanding_purchase/outstandingOrder" class="form-horizontal">
                @csrf
                <div class="form-group">
                    <label for="date">Outstanding Purchase Order</label> 
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', \Carbon\Carbon::now()->format('Y-m-d')) }}">
                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
            <button type="submit" class="btn btn-primary">Generate</button>
        </div>
    </div>
        </form>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/reports/outstanding_purchase/outstandingInvoice" class="form-horizontal">
                    @csrf
                    <div class="form-group">
                        <label for="date">Outstanding Purchase Invoice</label> 
                        <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', \Carbon\Carbon::now()->format('Y-m-d')) }}">
                        @error('date')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </form>
                </div>
            </div>
        </div>
@stop
