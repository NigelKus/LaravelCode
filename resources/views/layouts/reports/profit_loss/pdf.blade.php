<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Profit Loss Report</title>
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
    <div class="container">
        <h1>Profit Loss Report</h1>
        <p><strong>{{ $fromdate }} s/d {{ $todate }}</strong></p>
        <p><strong>Created Date: {{ $date }}</strong></p>

        <table class="table table-bordered" style="margin-bottom: 40px;">
            <thead>
                <tr>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
        
                @foreach ($pendapatan as $item)
                    <tr>
                        <td>{{ $item['coa']->name }}({{ $item['coa']->code }})</td>
                        <td>{{ number_format($item['total'], 2) }}</td>
                        <td></td>   
                    </tr>
                @endforeach
                
                <tr>
                    <td><strong>Total Pendapatan</strong></td>
                    <td></td>
                    <td><strong>{{ number_format(array_sum(array_column($pendapatan, 'total')), 2) }}</strong></td>

                </tr>
        
                <tr>
                    <td>{{ $HPP->name }}({{ $HPP->code }})</td>
                    <td></td>
                    <td><strong>{{ number_format($totalHPP, 2) }}</strong></td>
                </tr>

                <tr>
                    <td><strong>Laba Kotor</strong></td>
                    <td></td>
                    <td><strong>{{ number_format(array_sum(array_column($pendapatan, 'total')) - $totalHPP, 2) }}</strong></td>
                </tr>
                
        
                @foreach ($beban as $item)
                    <tr>
                        <td>{{ $item['coa']->name }}({{ $item['coa']->code }})</td>
                        <td>{{ number_format($item['total'], 2) }}</td> 
                        <td></td>
                    </tr>
                @endforeach
        
                <tr>
                    <td><strong>Total Beban</strong></td>
                    <td></td>
                    <td><strong>{{ number_format(array_sum(array_column($beban, 'total')), 2) }}</strong></td>
                </tr>
        
                <tr>
                    <td><strong>Laba Bersih Sebelum Pajak</strong></td>
                    <td></td> 
                    <td>
                        <strong>
                            {{ number_format(array_sum(array_column($pendapatan, 'total')) - array_sum(array_column($beban, 'total')) - $totalHPP, 2) }}
                        </strong>
                    </td>
                    
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
