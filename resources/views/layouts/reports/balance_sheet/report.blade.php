@extends('adminlte::page')

@section('title', 'Balance Sheet Report')

@section('content_header')
    <h1>Balance Sheet Report</h1>
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
            <form action="{{ route('balance_sheet.excel') }}" method="POST" class="form-horizontal" id="exportForm">
                @csrf
                <input type="hidden" name="dateStringDisplay" value="{{ $dateStringDisplay }}">


                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                    <!-- Logo Section -->
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('vendor/adminlte/dist/img/logoS.png'))) }}" 
                        style="height: 75px; width: 75px; margin-right: 20px;">
                
                    <!-- Text Section -->
                    <div style="text-align: center; flex: 1;">
                        <h2 style="margin: 0;">Balance Sheet</h2>
                        <p style="margin: 0;"><strong>{{ $dateStringDisplay }}</strong></p>
                        <p style="margin: 0;"><strong>Dibuat pada : {{ $createddate }}</strong></p>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
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
                    </div>
                    
                    <div class="col-md-6">
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
                                    @if(empty($totalLabaBerjalan))
                                    @else
                                    <td>Laba Berjalan({{ $codeLaba->code}})</td>
                                    <td>{{ number_format($totalLabaBerjalan, 2) }}</td>
                                    @endif
                                </tr>
                                {{-- <tr>
                                    @if(empty($totalLaba))
                                    @else
                                    <td>Laba Bertahan({{ $codeLabaBertahan->code}})</td>
                                    <td>{{ number_format($totalLaba, 2) }}</td>
                                    @endif
                                </tr> --}}
                                <tr>
                                    <td><strong>Total Liabilities & Equity</strong></td>
                                    <td>{{ number_format($totalPasiva, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <button type="button" class="btn btn-danger" onclick="submitForm('pdf')">PDF</button>
                    <button type="button" class="btn btn-success" onclick="submitForm('excel')">Excel</button>
                    <a href="{{ route('balance_sheet.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
            
            <script>
                function submitForm(type) {
                    const form = document.getElementById('exportForm');
                    if (type === 'pdf') {
                        form.action = '{{ route('balance_sheet.pdf') }}'; 
                    } else if (type === 'excel') {
                        form.action = '{{ route('balance_sheet.excel') }}'; 
                    }
                    form.submit();
                }
            </script>
            
        </div>
    </div>
@stop