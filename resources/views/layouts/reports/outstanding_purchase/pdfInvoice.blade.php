<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Outstanding Purchase Invoice Report</title>
    <style>
        .table-bordered {
            border-collapse: collapse; 
            width: 100%; 
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid black; 
            padding: 8px; 
            text-align: left; 
        }
    </style>
</head>
<body>
    <div style="position: relative; width: 100%; height: 150px;">
        <!-- Logo Section -->
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('vendor/adminlte/dist/img/logoS.png'))) }}" 
             style="position: absolute; top: 0; left: 0; height: 75px; width: 75px;">
    
        <!-- Text Section -->
        <div style="text-align: center; position: absolute; top: 0; left: 50%; transform: translateX(-50%);">
            <h1 style="margin: 0; font-size: 30px;">Outstanding Purchase Invoice List {{ $displaydate }}</h1>
            <p style="margin: 5px 0; font-size: 18px;"><strong>Dibuat pada : {{ $createddate }}</strong></p>
        </div>
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Invoice Code</th>
                <th>Purchase Order</th>
                <th>Supplier</th>
                <th>Description</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Remaining</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchaseInvoice as $invoice)
                    <tr>
                        <td>{{ $invoice->code }}</td>
                        <td>{{ $invoice->date }}</td>
                        <td>{{ $invoice->supplier->name ?? 'N/A' }}</td>
                        <td>{{ $invoice->description }}</td>
                        <td>{{ number_format($invoice->total_price, 0, '.', ',') }}</td>
                        <td>{{ number_format($invoice->paid, 0, '.', ',') }}</td>
                        <td>{{ number_format($invoice->remaining_price ?? 0, 0, '.', ',') }}</td>
                        <td>{{ $invoice->status }}</td>
                    </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

