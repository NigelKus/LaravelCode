@extends('adminlte::page')

@section('title', 'Outstanding Sales Invoice')

@section('content_header')
    <h1>Outstanding Sales Invoice</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form action="{{ route('outstanding_sales.excelOrder') }}" method="POST" class="form-horizontal" id="exportForm">
                @csrf
            <h3 class="card-title">Outstanding Sales Order List {{ $dates }}</h3>
            <input type="hidden" name="dates" value="{{ $dates }}">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="card-body">
            @if ($salesInvoice->isEmpty())
                <p>No outstanding sales invoices found before {{ $dates }}.</p>
            @else
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Invoice Code</th>
                            <th>Sales Order</th>
                            <th>Customer</th>
                            <th>Description</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Remaining</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($salesInvoice as $invoice)
                                <tr>
                                    <td>{{ $invoice->code }}</td>
                                    <td>{{ $invoice->date }}</td>
                                    <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                                    <td>{{ $invoice->description }}</td>
                                    <td>{{ number_format($invoice->total_price, 0, '.', ',') }}</td>
                                    <td>{{ number_format($invoice->paid, 0, '.', ',') }}</td>
                                    <td>{{ number_format($invoice->remaining_price ?? 0, 0, '.', ',') }}</td>
                                    <td>{{ $invoice->status }}</td>
                                </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
            <div class="form-group mt-3">
                <button type="button" class="btn btn-danger" onclick="submitForm('pdf')">PDF</button>
                <button type="button" class="btn btn-success" onclick="submitForm('excel')">Excel</button>
                <a href="{{ route('outstanding_sales.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>

    <script>
        function submitForm(type) {
            const form = document.getElementById('exportForm');
            if (type === 'pdf') {
                form.action = '{{ route('outstanding_sales.pdfInvoice') }}'; 
            } else if (type === 'excel') {
                form.action = '{{ route('outstanding_sales.excelInvoice') }}'; 
            }
            form.submit();
        }
    </script>
    </div>        
@stop