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

            <h2 class="text-center">General Ledger</h2>
            <p class="text-center"><strong>{{ $fromdate }} s/d {{ $todate }}</strong></p>
            @if(isset($results))
            @foreach($results as $result)
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <strong>{{ $result['coa']->name }} ({{ $result['coa']->code }})</strong>
                        </tr>
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
                            <td>{{ $result['balance'] }}</td>
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
                                    <td>{{ $posting->amount }}</td>
                                    <td></td>
                                    <td>{{ $result['balance'] += $posting->amount }}</td>
                                    @php $totalDebit += $posting->amount; @endphp
                                @else
                                    <td></td>
                                    <td>{{ abs($posting->amount) }}</td>
                                    <td>{{ $result['balance'] += $posting->amount }}</td>
                                    @php $totalCredit += abs($posting->amount); @endphp
                                @endif
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>Total</td>
                            <td>{{ $totalDebit }}</td>
                            <td>{{ $totalCredit }}</td>
                            <td>{{ $result['balance'] }}</td>
                        </tr>
                    </tbody>
                </table>
            @endforeach
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <strong>{{ $coa->name }} ({{ $coa->code }})</strong>
                    </tr>
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
                        <td>{{ $balance }}</td>
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
                                <td>{{ $posting->amount }}</td>
                                <td></td>
                                <td>{{ $balance += $posting->amount }}</td>
                                @php $totalDebit += $posting->amount; @endphp
                            @else
                                <td></td>
                                <td>{{ abs($posting->amount) }}</td>
                                <td>{{ $balance += $posting->amount }}</td>
                                @php $totalCredit += abs($posting->amount); @endphp
                            @endif
                        </tr>
                    @endforeach
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td>Total</td>
                        <td>{{ $totalDebit }}</td>
                        <td>{{ $totalCredit }}</td>
                        <td>{{ $balance }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
        
            <div class="form-group mt-3">
                <button type="submit" class="btn btn-primary">PDF</button>
                <a href="{{ route('general_ledger.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
@stop
