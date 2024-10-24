@extends('adminlte::page')

@section('title', 'Profit Loss Generate')

@section('content_header')
    <h1>Generate Profit Loss</h1>
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
            <form action="{{ route('profit_loss.excel') }}" method="POST" class="form-horizontal" id="exportForm">
                @csrf
                <input type="hidden" name="fromdate" value="{{ $fromdate }}">
                <input type="hidden" name="todate" value="{{ $todate }}">
            
                <h2 class="text-left">Profit Loss</h2>
                <p class="text-left"><strong>{{ $fromdate }} s/d {{ $todate }}</strong></p>
                
                <hr>
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
                <div class="form-group mt-3">
                    <button type="button" class="btn btn-danger" onclick="submitForm('pdf')">PDF</button>
                    <button type="button" class="btn btn-success" onclick="submitForm('excel')">Excel</button>
                    <a href="{{ route('profit_loss.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <script>
                function submitForm(type) {
                    const form = document.getElementById('exportForm');
                    if (type === 'pdf') {
                        form.action = '{{ route('profit_loss.pdf') }}'; 
                    } else if (type === 'excel') {
                        form.action = '{{ route('profit_loss.excel') }}'; 
                    }
                    form.submit();
                }
            </script>
        </div>
    </div>
@stop