<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Balance Sheet Report</title>
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
    <div style="position: relative; width: 100%; height: 100px;">
        <!-- Logo Section -->
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('vendor/adminlte/dist/img/logoS.png'))) }}" 
            style="position: absolute; top: 50%; left: 0; transform: translateY(-50%); height: 75px; width: 75px;">
        <!-- Text Section -->
        <div style="text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <h1 style="margin: 0;">Laporan Neraca Saldo</h1>
            <p style="margin: 0;"><strong>{{ $dateStringDisplay }}</strong></p>
            <p style="margin: 0;"><strong>Dibuat pada: {{ $createddate }}</strong></p>
        </div>
    </div>
    
            <table class="table table-bordered" style="margin-bottom: 40px;">
                <thead>
                    <tr>
                        <th colspan="2">Asset</th>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($totalasset as $item)
                    <tr>
                        <td>{{ $item['coa']->name }}({{ $item['coa']->code }})</td>
                        <td>{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
                    <tr>
                        <td><strong>Total Asset</strong></td>
                        <td>{{ number_format($totalActiva, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            <table class="table table-bordered" style="margin-bottom: 40px;">
                <thead>
                    <tr>
                        <th colspan="2">Liabilites & Equity</th>
                    </tr>
                </thead>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Liabilities</strong></td>
                        <td></td>
                    </tr>
                @foreach ($totalUtang as $item)
                    <tr>
                        <td>{{ $item['coa']->name }}({{ $item['coa']->code }})</td>
                        <td>{{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
                    <tr>
                        <td><strong>Equity</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        @if(empty($totalModal))
                        @else
                        <td>{{ $codeModal->name }}({{ $codeModal->code }})</td>
                        <td>{{ number_format($totalModal, 2) }}</td>
                        @endif
                    </tr>
                    <tr>
                        @if(empty($totalLaba))
                        @else
                        <td>Laba Berjalan({{ $codeLaba->code}})</td>
                        <td>{{ number_format($totalLaba, 2) }}</td>
                        @endif
                    </tr>
                    <tr>
                        <td><strong>Total Liabilities & Equity</strong></td>
                        <td>{{ number_format($totalPasiva, 2) }}</td>
                    </tr>
                </tbody>
            </table>

</body>
</html>