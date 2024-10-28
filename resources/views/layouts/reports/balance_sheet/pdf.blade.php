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
    <h1>Balance Sheet Report</h1>
    <p><strong>{{ $dateStringDisplay }} </strong></p>
        {{-- <table class="table table-bordered" style="margin-bottom: 40px;">
            <thead>
            </thead>
            <td colspan="2">
                Asset
            </td>
            <td colspan="2">
                Liablities & Equity
            </td>

            <tr>
                <td>Nama</td>
                <td>Jumlah</td>
                <td>Nama</td>
                <td>Jumlah</td>
            </tr>

            @php
                $firstrun = 0;
                $displayedCoaCodes = [];
                $isDisplayed = false;
            @endphp
            @foreach ($totalasset as $item)
            <tr>
                <td>{{ $item['coa']->name }}({{ $item['coa']->code }})</td>
                <td>{{ number_format($item['total'], 2) }}</td>

                @foreach ($totalUtang as $liabilityItem)
                    @if ($firstrun == 0)
                        <td><strong>Liabilities</strong></td>
                        <td></td>
                        @php
                        $firstrun = 1;
                        @endphp
                        @elseif (!$isDisplayed && !in_array($liabilityItem['coa']->code, $displayedCoaCodes))
                            <td>{{ $liabilityItem['coa']->name }} ({{ $liabilityItem['coa']->code }})</td>
                            <td>{{ number_format($liabilityItem['total'], 2) }}</td>

                            @php
                                $displayedCoaCodes[] = $liabilityItem['coa']->code;
                                $isDisplayed = true;
                                $firstrun = 2;
                            @endphp
                    @else
                        @if($firstrun == 2)
                            <td><strong>Equity</strong></td>
                            <td></td>
                            @php
                                $firstrun = 3;
                            @endphp
                        @endif
                    @endif

                    @if($firstrun == 3)
                    <td><strong>Equity</strong></td>
                    <td></td>
                    @php
                        $firstrun = 3;
                    @endphp
                @endif
                @endforeach
            </tr>
            @endforeach
                <tr>
                    <td><strong>Total Asset</strong></td>
                    <td>{{ number_format($totalActiva, 2) }}</td>
                </tr>
            </tbody>

            
        </table> --}}

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