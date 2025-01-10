@extends('adminlte::page')

@section('title', 'Outstanding Sales Order')

@section('content_header')
    <h1>Outstanding Sales Order</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <form action="{{ route('outstanding_sales.excelOrder') }}" method="POST" class="form-horizontal" id="exportForm">
                @csrf
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <!-- Logo Section -->
                <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('vendor/adminlte/dist/img/logoS.png'))) }}" 
                    style="height: 75px; width: 75px; margin-right: 20px;">
            
                <!-- Text Section -->
                <div style="text-align: center; flex: 1;">
                    <h2 style="margin: 0; font-size: 30px;">Outstanding Sales Order List {{ $displaydate }}</h2>
                    <p style="margin: 0; font-size: 18px;"><strong>Created At : {{ $createddate }}</strong></p>
                </div>
            </div>

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
            @if ($salesOrder->isEmpty())
                <p>No outstanding sales orders found before {{ $dates }}.</p>
            @else
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Sent</th>
                            <th>Remaining</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($salesOrder as $order)
                        <tr>
                            <td>{{ $order->code }}</td>
                            <td>{{ $order->date }}</td>
                            <td>{{ $order->customer->name ?? 'N/A' }}</td>
                            <td>{{ $order->description }}</td>
                            <td>{{ $order->total_quantity }}</td>
                            <td>{{ $order->total_quantity_sent }}</td>
                            <td>{{ $order->quantity_difference }}</td>
                            <td>{{ $order->status }}</td>
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
        </form>

        <script>
            function submitForm(type) {
                const form = document.getElementById('exportForm');
                if (type === 'pdf') {
                    form.action = '{{ route('outstanding_sales.pdfOrder') }}'; 
                } else if (type === 'excel') {
                    form.action = '{{ route('outstanding_sales.excelOrder') }}'; 
                }
                form.submit();
            }
        </script>
        </div>
    </div>        
@stop
