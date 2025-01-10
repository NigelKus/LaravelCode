<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Outstanding Sales Order Report</title>
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
            <h1 style="margin: 0; font-size: 30px;">Outstanding Sales Order List {{ $dates }}</h1>
            <p style="margin: 5px 0; font-size: 18px;"><strong>Dibuat pada : {{ $createddate }}</strong></p>
        </div>
    </div>


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
</body>
</html>

