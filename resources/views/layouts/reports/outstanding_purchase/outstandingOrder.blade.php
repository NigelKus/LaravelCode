@extends('adminlte::page')

@section('title', 'Outstanding Purchase Order')

@section('content_header')
    <h1>Outstanding Purchase Order</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form action="{{ route('outstanding_purchase.excelOrder') }}" method="POST" class="form-horizontal" id="exportForm">
                @csrf
            <h3 class="card-title">Outstanding Purchase Order List {{ $dates }}</h3>
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
            @if ($purchaseOrder->isEmpty())
                <p>No outstanding purchase orders found before {{ $dates }}.</p>
            @else
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Sent</th>
                            <th>Remaining</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($purchaseOrder as $order)
                        <tr>
                            <td>{{ $order->code }}</td>
                            <td>{{ $order->date }}</td>
                            <td>{{ $order->supplier->name ?? 'N/A' }}</td>
                            <td>{{ $order->description }}</td>
                            <td>{{ $order->total_quantity }}</td>
                            <td>{{ $order->total_quantity_sent }}</td>
                            <td>{{ $order->quantity_difference }}</td>
                            <td>{{ $order->status}}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
            <div class="form-group mt-3">
                <button type="button" class="btn btn-danger" onclick="submitForm('pdf')">PDF</button>
                <button type="button" class="btn btn-success" onclick="submitForm('excel')">Excel</button>
                <a href="{{ route('outstanding_purchase.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

        <script>
            function submitForm(type) {
                const form = document.getElementById('exportForm');
                if (type === 'pdf') {
                    form.action = '{{ route('outstanding_purchase.pdfOrder') }}'; 
                } else if (type === 'excel') {
                    form.action = '{{ route('outstanding_purchase.excelOrder') }}'; 
                }
                form.submit();
            }
        </script>
        </div>
    </div>        
@stop