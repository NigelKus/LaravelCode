@extends('adminlte::page')

@section('title', 'Balance Sheet Index')

@section('content_header')
    <h1>Balance Sheet</h1>
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

        <form method="POST" action="/admin/reports/balance_sheet/generate" class="form-horizontal">
            @csrf
            <div class="form-group">
                <label for="month">Select Month</label>
                <select class="form-control @error('month') is-invalid @enderror" id="month" name="month">
                    <option value="">Select Month</option>
                    @foreach (range(1, 12) as $month)
                        <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" {{ old('month') == str_pad($month, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $month)->format('F') }}
                        </option>
                    @endforeach
                </select>
                @error('month')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="year">Select Year</label>
                <select class="form-control @error('year') is-invalid @enderror" id="year" name="year">
                    <option value="">Select Year</option>
                    @for ($year = date('Y') - 10; $year <= date('Y') + 10; $year++)
                        <option value="{{ $year }}" {{ old('year') == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endfor
                </select>
                @error('year')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        
            <button type="submit" class="btn btn-primary">Generate</button>
        </form>
        </div>
    </div>
@endsection

