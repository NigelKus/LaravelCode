@extends('adminlte::page')

@section('title', 'Journal Manual Create')

@section('content_header')
    <h1>Journal Manual</h1>
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
                <div class="form-group">
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
                </div>

                {{-- <div class="form-group">
                    <label for="transaction">Kas/Bank</label>
                    <select class="form-control @error('transaction') is-invalid @enderror" id="transaction" name="transaction" required>
                        <option value="">Select a type of transaction</option>
                        <option value="kas" {{ old('transaction') == 'kas' ? 'selected' : '' }}>Kas</option>
                        <option value="bank" {{ old('transaction') == 'bank' ? 'selected' : '' }}>Bank</option>
                    </select>
                    @error('transaction')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div> --}}

                <div class="form-group">
                    <label for="coas">Kas/Bank</label>
                    <select class="form-control @error('coas') is-invalid @enderror" id="coas" name="coas" required>
                        <option value="">Select a type of transaction</option>
                        @foreach($CoAs as $coa)
                            <option value="{{ $coa->id }}" {{ old('coas') == $coa->id ? 'selected' : '' }}>
                                {{ $coa->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('coas')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                

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
                    <label for="amount">Amount</label>
                    <input type="number" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" required>
                    @error('amount')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
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
                        <h3 class="card-title">Chart of Accounts</h3>
                    </div>
                    <div class="card-body">
                        <a href="#" id="btn-add-coa-line" class="btn btn-sm btn-outline-info btn-labeled">
                            <span class="btn-label"></span>
                            Add Chart of Account
                        </a>

                        <table class="table table-bordered mt-3" id="coas-table">
                            <thead>
                                <tr>
                                    <th>Chart of Account</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(range(1, 1) as $num)
                                    @include('layouts.reports.journal.partials.coa_line', [
                                        'coa_id' => old('coa_id.' . ($num - 1)),
                                        'amount' => old('amount.' . ($num - 1)),
                                    ])
                                @endforeach
                            </tbody>
                        </table>

                        <table style="display: none;" id="coa-line-template">
                            @include('layouts.reports.journal.partials.coa_line', [
                                'coa_id' => '',
                                'amount' => '',
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
            const coasTable = document.getElementById('coas-table').querySelector('tbody');
            const coaLineTemplate = document.getElementById('coa-line-template').innerHTML;

            document.getElementById('btn-add-coa-line').addEventListener('click', function(e) {
                e.preventDefault();
                const newRow = document.createElement('tr');
                newRow.innerHTML = coaLineTemplate;
                coasTable.appendChild(newRow);
            });

            coasTable.addEventListener('change', function(e) {
                if (e.target.name.startsWith('coa_id')) {
                    const coaSelects = coasTable.querySelectorAll('select[name^="coa_id"]');
                    const selectedCoaIds = Array.from(coaSelects).map(select => select.value);
                    const currentSelect = e.target.value;

                    if (selectedCoaIds.filter(id => id === currentSelect).length > 1) {
                        alert('This Chart of Account is already selected.');
                        e.target.value = ''; 
                    }
                }
            });

            coasTable.addEventListener('click', function(e) {
                if (e.target.classList.contains('del-row')) {
                    e.target.closest('tr').remove();
                }
            });
        });
    </script>
    @endpush

@stop
