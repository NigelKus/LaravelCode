@extends('adminlte::page')

@section('title', 'Balance Sheet Report')

@section('content_header')
    <h1>Balance Sheet Report</h1>
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
            <form action="{{ route('balance_sheet.excel') }}" method="POST" class="form-horizontal" id="exportForm">
                @csrf

                <h2 class="text-left">Balance Sheet</h2>
                <p class="text-left"><strong>{{ $dateString }}</strong></p>
                <hr>



                <div class="form-group mt-3">
                    <button type="button" class="btn btn-danger" onclick="submitForm('pdf')">PDF</button>
                    <button type="button" class="btn btn-success" onclick="submitForm('excel')">Excel</button>
                    <a href="{{ route('balance_sheet.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <script>
                function submitForm(type) {
                    const form = document.getElementById('exportForm');
                    if (type === 'pdf') {
                        form.action = '{{ route('balance_sheet.pdf') }}'; 
                    } else if (type === 'excel') {
                        form.action = '{{ route('balance_sheet.excel') }}'; 
                    }
                    form.submit();
                }
            </script>
            
        </div>
    </div>
@stop