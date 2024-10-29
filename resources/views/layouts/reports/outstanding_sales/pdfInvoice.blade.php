<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Outstanding Sales Invoice Report</title>
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
    <h3 class="card-title">Outstanding Sales Invoice List {{ $dates }}</h3>
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
</body>
</html>

