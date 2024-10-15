<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>General Ledger Report</title>
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
        <h1>General Ledger Report</h1>
        <p><strong>{{ $fromdate }} s/d {{ $todate }}</strong></p>
        <p><strong>Created Date: {{ $date }}</strong></p>

        @if(isset($results))
            @foreach($results as $result)
                <table class="table table-bordered" style="margin-bottom: 40px;">
                    <thead>
                        <tr>
                            <th colspan="7" style="text-align: left;">
                                <strong>{{ $result['coa']->name }} ({{ $result['coa']->code }})</strong>
                            </th>
                        </tr>
                        <tr >
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Journal Name</th>
                            <th>Kode Transaksi</th>
                            <th style="width: 75px;">Debit</th> 
                            <th style="width: 75px;">Kredit</th> 
                            <th style="width: 75px;">Balance</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="font-size: 14px;">Saldo Awal</td>
                            <td style="font-size: 14px;">{{ $fromdate }}</td>
                            <td style="font-size: 12px;">Saldo Awal</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td style="font-size: 14px;">{{ number_format($result['balance'], 0, ',', '.') }}</td>
                        </tr>
                        @php
                            $totalCredit = 0; 
                            $totalDebit = 0; 
                        @endphp
                        @foreach($result['postings'] as $posting)
                            <tr>
                                <td style="font-size: 12px;">{{ $posting->journal->code }}</td>
                                <td style="font-size: 12px;">{{ $posting->date }}</td>
                                <td style="font-size: 10px;">{{ $posting->journal->name }}</td> 
                                <td style="font-size: 14px;">{{ $posting->journal->description }}</td>
                                @if($posting->amount > 0)
                                    <td style="font-size: 14px;">{{ number_format($posting->amount,0, ',', '.' )}}</td>
                                    <td></td>
                                    <td style="font-size: 14px;">{{ number_format($result['balance'] += $posting->amount,0, ',', '.' )}}</td>
                                    @php $totalDebit += $posting->amount; @endphp
                                @else
                                    <td></td>
                                    <td style="font-size: 14px;">{{ number_format(abs($posting->amount),0, ',', '.' )}}</td>
                                    <td style="font-size: 14px;">{{ number_format($result['balance'] += $posting->amount, 0, ',', '.') }}</td>
                                    @php $totalCredit += abs($posting->amount); @endphp
                                @endif
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
                            <td style="font-size: 14px;">{{ number_format($totalDebit,0, ',', '.' )}}</td>
                            <td style="font-size: 14px;">{{ number_format($totalCredit,0, ',', '.' )}}</td>
                            <td style="font-size: 14px;">{{ number_format($result['balance'],0, ',', '.' )}}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @else
            <table class="table table-bordered" style="margin-bottom: 40px;">
                <thead>
                    <tr>
                        <th colspan="7" style="text-align: left;">
                            <strong>{{ $coa->name }} ({{ $coa->code }})</strong>
                            <input type="hidden" name="coa_id" value="{{ $coa->id ?? '' }}">
                        </th>
                    </tr>
                    <tr>
                        <th>Kode</th>
                        <th>Tanggal</th>
                        <th>Journal Name</th>
                        <th>Kode Transaksi</th>
                        <th style="width: 75px;">Debit</th> 
                        <th style="width: 75px;">Kredit</th> 
                        <th style="width: 75px;">Balance</th> 
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-size: 14px;">Saldo Awal</td>
                        <td style="font-size: 14px;">{{ $fromdate }}</td>
                        <td style="font-size: 12px;">Saldo Awal</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td style="font-size: 12px;">{{ number_format($balance, 0, ',', '.') }}</td>
                    </tr>
                    @php
                        $totalCredit = 0; 
                        $totalDebit = 0; 
                    @endphp
                    @foreach($postings as $posting)
                        <tr>
                            <td style="font-size: 12px;">{{ $posting->journal->code }}</td>
                            <td style="font-size: 12px;">{{ $posting->date }}</td>
                            <td style="font-size: 10px;">{{ $posting->journal->name }}</td> 
                            <td style="font-size: 14px;">{{ $posting->journal->description }}</td>
                            @if($posting->amount > 0)
                                <td style="font-size: 14px;">{{ number_format($posting->amount,0, ',', '.' )}}</td>
                                <td></td>
                                <td style="font-size: 14px;">{{ number_format($balance += $posting->amount,0, ',', '.' )}}</td>
                                @php $totalDebit += $posting->amount; @endphp
                            @else
                                <td></td>
                                <td style="font-size: 14px;">{{ number_format(abs($posting->amount),0, ',', '.' )}}</td>
                                <td style="font-size: 14px;">{{ number_format($balance += $posting->amount,0, ',', '.' )}}</td>
                                @php $totalCredit += abs($posting->amount); @endphp
                            @endif
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
                        <td style="font-size: 14px;">{{ number_format($totalDebit,0, ',', '.' )}}</td>
                        <td style="font-size: 14px;">{{ number_format($totalCredit,0, ',', '.' )}}</td>
                        <td style="font-size: 14px;">{{ number_format($balance,0, ',', '.' )}}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
