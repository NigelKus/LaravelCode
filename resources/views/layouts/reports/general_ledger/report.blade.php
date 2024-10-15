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
            <form method="POST" action="{{ route('general_ledger.pdf') }}" class="form-horizontal">
                @csrf
            <input type="hidden" name="fromdate" value="{{ $fromdate }}">
            <input type="hidden" name="todate" value="{{ $todate }}">
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
                    <th colspan="7" >
                        <strong>{{ $coa->name }} ({{ $coa->code }})</strong>
                        <input type="hidden" name="coa_id" value="{{ $coa->id ?? '' }}">
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
                <button type="submit" class="btn btn-primary">EXCEL</button>
                <a href="{{ route('general_ledger.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
            <form>
        </div>
    </div>
@stop
