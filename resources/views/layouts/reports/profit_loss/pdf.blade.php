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
        {{-- <h1>Profit Loss Report</h1>
        <p><strong>{{ $fromdate }} s/d {{ $todate }}</strong></p>
        <p><strong>Created Date: {{ $date }}</strong></p> --}}

        <div style="position: relative; width: 100%; height: 150px;">
            <!-- Logo Section -->
            <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('vendor/adminlte/dist/img/logoS.png'))) }}" 
                 style="position: absolute; top: 0; left: 0; height: 75px; width: 75px;">
        
            <!-- Text Section -->
            <div style="text-align: center; position: absolute; top: 0; left: 50%; transform: translateX(-50%);">
                <h1 style="margin: 0; font-size: 30px;">Laporan Rugi Laba</h1>
                <p style="margin: 5px 0; font-size: 18px;"><strong>{{ $displayfromdate }} s/d {{ $displaytodate }}</strong></p>
                <p style="margin: 5px 0; font-size: 18px;"><strong>Dibuat pada : {{ $createddate }}</strong></p>
            </div>
        </div>

        <table class="table table-bordered" style="margin-bottom: 40px;">
            <thead>
                <tr>
                    <th>Keterangan</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Saldo Awal</strong></td>
                    <td>{{ number_format($saldoAwal, 2) }}</td>
                    <td></td>   
                </tr>
        
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
                    <td><strong>{{ number_format(array_sum(array_column($pendapatan, 'total')) - $totalHPP + $saldoAwal, 2) }}</strong></td>
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
                            {{ number_format(array_sum(array_column($pendapatan, 'total')) - array_sum(array_column($beban, 'total')) - $totalHPP , 2) }}
                        </strong>
                    </td>
                    
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
