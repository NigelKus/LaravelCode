@extends('adminlte::page')

@section('title', 'General Ledger Report')

@section('content_header')
    <h1>General Ledger Report</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('general_ledger.excel') }}" method="POST" class="form-horizontal" id="exportForm">
                @csrf
                <input type="hidden" name="fromdate" value="{{ $fromdate }}">
                <input type="hidden" name="todate" value="{{ $todate }}">
                <input type="hidden" name="coa_id" value="{{ $coa->id ?? '' }}">
            
                <h2 class="text-left">General Ledger</h2>
                <p class="text-left"><strong>{{ $fromdate }} s/d {{ $todate }}</strong></p>
                <hr>
                
                @if(isset($results))
                    @foreach($results as $result)
                        <table class="table table-bordered" style="margin-bottom: 40px;">
                            <thead>
                                <th colspan="7" style="text-align: left;">
                                    <strong>{{ $result['coa']->name }} ({{ $result['coa']->code }})</strong>
                                </th>
                                <tr>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th>Journal Name</th>
                                    <th>Kode Transaksi</th>
                                    <th>Debit</th> 
                                    <th>Kredit</th> 
                                    <th>Balance</th> 
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Saldo Awal</td>
                                    <td>{{ $fromdate }}</td>
                                    <td>Saldo Awal</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>{{ number_format($result['balance'], 0, ',', '.') }}</td>
                                </tr>
                                @php
                                    $totalCredit = 0; 
                                    $totalDebit = 0; 
                                @endphp
                                @foreach($result['postings'] as $posting)
                                    <tr>
                                        <td>{{ $posting->journal->code }}</td>
                                        <td>{{ $posting->date }}</td>
                                        <td>{{ $posting->journal->name }}</td> 
                                        <td>{{ $posting->journal->description }}</td>
                                        @if($posting->amount > 0)
                                            <td>{{ number_format($posting->amount, 0, ',', '.') }}</td>
                                            <td></td>
                                            <td>{{ number_format($result['balance'] += $posting->amount, 0, ',', '.') }}</td>
                                            @php $totalDebit += $posting->amount; @endphp
                                        @else
                                            <td></td>
                                            <td>{{ number_format(abs($posting->amount), 0, ',', '.') }}</td>
                                            <td>{{ number_format($result['balance'] += $posting->amount, 0, ',', '.') }}</td>
                                            @php $totalCredit += abs($posting->amount); @endphp
                                        @endif
                                    </tr>
                                @endforeach
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>Total</td>
                                    <td>{{ number_format( $totalDebit, 0, ',', '.') }}</td>
                                    <td>{{ number_format( $totalCredit, 0, ',', '.') }}</td>
                                    <td>{{ number_format( $result['balance'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @endforeach
                @else
                    <table class="table table-bordered">
                        <thead>
                            <th colspan="7" >
                                <strong>{{ $coa->name }} ({{ $coa->code }})</strong>
                            </th>
                            <tr>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Journal Name</th>
                                <th>Kode Transaksi</th>
                                <th>Debit</th> 
                                <th>Kredit</th> 
                                <th>Balance</th> 
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Saldo Awal</td>
                                <td>{{ $fromdate }}</td>
                                <td>Saldo Awal</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>{{ number_format($balance, 0, ',', '.') }}</td>
                            </tr>
                            @php
                                $totalCredit = 0; 
                                $totalDebit = 0; 
                            @endphp
                            @foreach($postings as $posting)
                                <tr>
                                    <td>{{ $posting->journal->code }}</td>
                                    <td>{{ $posting->date }}</td>
                                    <td>{{ $posting->journal->name }}</td> 
                                    <td>{{ $posting->journal->description }}</td>
                                    @if($posting->amount > 0)
                                        <td>{{ number_format($posting->amount, 0, ',', '.') }}</td>
                                        <td></td>
                                        <td>{{ number_format($balance += $posting->amount, 0, ',', '.') }}</td>
                                        @php $totalDebit += $posting->amount; @endphp
                                    @else
                                        <td></td>
                                        <td>{{ number_format(abs($posting->amount), 0, ',', '.') }}</td>
                                        <td>{{ number_format($balance += $posting->amount, 0, ',', '.') }}</td>
                                        @php $totalCredit += abs($posting->amount); @endphp
                                    @endif
                                </tr>
                            @endforeach
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>Total</td>
                                <td>{{ number_format( $totalDebit, 0, ',', '.') }}</td>
                                <td>{{ number_format( $totalCredit, 0, ',', '.') }}</td>
                                <td>{{ number_format( $balance, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
                
                <div class="form-group mt-3">
                    <button type="button" class="btn btn-danger" onclick="submitForm('pdf')">PDF</button>
                    <button type="button" class="btn btn-success" onclick="submitForm('excel')">Excel</button>
                    <a href="{{ route('general_ledger.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <script>
                function submitForm(type) {
                    const form = document.getElementById('exportForm');
                    if (type === 'pdf') {
                        form.action = '{{ route('general_ledger.pdf') }}'; 
                    } else if (type === 'excel') {
                        form.action = '{{ route('general_ledger.excel') }}'; 
                    }
                    form.submit();
                }
            </script>
            
        </div>
    </div>
@stop
