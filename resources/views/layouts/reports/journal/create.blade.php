@extends('adminlte::page')

@section('title', 'Journal Voucher Create')

@section('content_header')
    <h1>Journal Voucher</h1>
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
            <form id="journal-form" method="POST" action="/admin/reports/journal/store" class="form-horizontal">
                @csrf
                {{-- <div class="form-group">
                    <label for="type">Type</label>
                    <select class="form-control @error('type') is-invalid @enderror" id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="in" {{ old('type') == 'in' ? 'selected' : '' }}>In (Debit)</option>
                        <option value="out" {{ old('type') == 'out' ? 'selected' : '' }}>Out (Kredit)</option>
                    </select>
                    @error('type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div> --}}

                <div class="form-group">
                    <label for="date">Date</label> 
                    <input type="datetime-local" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', \Carbon\Carbon::now()->addHours(7)->format('Y-m-d\TH:i')) }}">
                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <input type="text" class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description') }}">
                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title" id="coa-title">Debit Chart of Accounts</h3>
                    </div>
                    <div class="card-body">
                        <a href="#" id="btn-add-debit-coa-line" class="btn btn-sm btn-outline-info btn-labeled">
                            <span class="btn-label"></span>
                            Add Chart of Account
                        </a>
                
                        <table class="table table-bordered mt-3" id="debit-coas-table">
                            <thead>
                                <tr>
                                    <th>Chart of Account</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(range(1, 1) as $num)
                                    @include('layouts.reports.journal.partials.coa_line_copy', [
                                        'coa_id' => old('1coa_id.' . ($num - 1)),
                                        'amount' => old('1amount.' . ($num - 1)),
                                        'description' => old('1description.' . ($num - 1)),
                                    ])
                                @endforeach
                            </tbody>
                        </table>
                
                        <table style="display: none;" id="debit-coa-line-template">
                            @include('layouts.reports.journal.partials.coa_line_copy', [
                                'coa_id' => '',
                                'amount' => '',
                                'description' => '',
                            ])
                        </table>
                    </div>
                </div>
                
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title" id="coa-title1">Credit Chart of Accounts</h3>
                    </div>
                    <div class="card-body">
                        <a href="#" id="btn-add-credit-coa-line" class="btn btn-sm btn-outline-info btn-labeled">
                            <span class="btn-label"></span>
                            Add Chart of Account
                        </a>
                
                        <table class="table table-bordered mt-3" id="credit-coas-table">
                            <thead>
                                <tr>
                                    <th>Chart of Account</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(range(1, 1) as $num)
                                    @include('layouts.reports.journal.partials.coa_line', [
                                        'coa_id' => old('coa_id.' . ($num - 1)),
                                        'amount' => old('amount.' . ($num - 1)),
                                        'description' => old('1description.' . ($num - 1)),
                                    ])
                                @endforeach
                            </tbody>
                        </table>
                
                        <table style="display: none;" id="credit-coa-line-template">
                            @include('layouts.reports.journal.partials.coa_line', [
                                'coa_id' => '',
                                'amount' => '',
                                'description' => '',
                            ])
                        </table>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <a href="{{ route('journal.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const debitCoasTable = document.getElementById('debit-coas-table').querySelector('tbody');
            const debitCoaLineTemplate = document.getElementById('debit-coa-line-template').innerHTML;

            document.getElementById('btn-add-debit-coa-line').addEventListener('click', function(e) {
                e.preventDefault();
                const newRow = document.createElement('tr');
                newRow.innerHTML = debitCoaLineTemplate;
                debitCoasTable.appendChild(newRow);
            });

            const creditCoasTable = document.getElementById('credit-coas-table').querySelector('tbody');
            const creditCoaLineTemplate = document.getElementById('credit-coa-line-template').innerHTML;

            document.getElementById('btn-add-credit-coa-line').addEventListener('click', function(e) {
                e.preventDefault();
                const newRow = document.createElement('tr');
                newRow.innerHTML = creditCoaLineTemplate;
                creditCoasTable.appendChild(newRow);
            });


                    [debitCoasTable, creditCoasTable].forEach(table => {
                table.addEventListener('change', function(e) {
                    if (e.target.name.startsWith('coa_id')) {
                        const coaSelects = table.querySelectorAll('select[name^="coa_id"]');
                        const selectedCoaIds = Array.from(coaSelects).map(select => select.value);
                        const currentSelect = e.target.value;

                        // if (selectedCoaIds.filter(id => id === currentSelect).length > 1) {
                        //     alert('This Chart of Account is already selected.');
                        //     e.target.value = ''; 
                        // }
                    }
                });

                table.addEventListener('click', function(e) {
                    if (e.target.classList.contains('del-row')) {
                        e.target.closest('tr').remove();
                    }
                });
            });
        });

            const typeSelect = document.getElementById('type');
            const coaTitle = document.getElementById('coa-title');
            const coaTitle1 = document.getElementById('coa-title1');

            typeSelect.addEventListener('change', function() {
                if (this.value === 'in') {
                    coaTitle.textContent = 'Debit Chart of Accounts';
                    coaTitle1.textContent = 'Credit Chart of Accounts';
                } else if (this.value === 'out') {
                    coaTitle.textContent = 'Credit Chart of Accounts';
                    coaTitle1.textContent = 'Debit Chart of Accounts';
                } else {
                    coaTitle.textContent = 'Chart of Accounts'; 
                    coaTitle1.textContent1 = 'Chart of Accounts'; 
                }
            });
    </script>
    @endpush

@stop
