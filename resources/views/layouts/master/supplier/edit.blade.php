@extends('adminlte::page')

@section('title', 'Edit Supplier')

@section('content_header')
    <h1>Edit Supplier</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Supplier Information</h3>
        </div>
        <form method="POST" action="{{ route('supplier.update', $supplier->id) }}" class="form-horizontal">
            @csrf
            @method('PUT')
            <div class="card-body">

                <!-- Display success message -->
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Display error messages -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="form-group">
                    <label for="code">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $supplier->code) }}" required>
                    @error('code')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $supplier->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="supplier_category">Supplier Category</label>
                    <select class="form-control @error('supplier_category') is-invalid @enderror" id="supplier_category" name="supplier_category" required>
                        <option value="">Select a Category</option>
                        <option value="Retail" {{ old('supplier_category', $supplier->supplier_category) == 'Retail' ? 'selected' : '' }}>Retail</option>
                        <option value="Wholesale" {{ old('supplier_category', $supplier->supplier_category) == 'Wholesale' ? 'selected' : '' }}>Wholesale</option>
                        <option value="Online" {{ old('supplier_category', $supplier->supplier_category) == 'Online' ? 'selected' : '' }}>Online</option>
                        <!-- Add more options as needed -->
                    </select>
                    @error('supplier_category')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $supplier->address) }}">
                    @error('address')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="number" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}">
                    @error('phone')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description">{{ old('description', $supplier->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="birth_date">Birth Date</label>
                    <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" value="{{ old('birth_date', optional($supplier->birth_date)->format('Y-m-d')) }}">
                    @error('birth_date')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="birth_city">Birth City</label>
                    <input type="text" class="form-control @error('birth_city') is-invalid @enderror" id="birth_city" name="birth_city" value="{{ old('birth_city', $supplier->birth_city) }}">
                    @error('birth_city')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $supplier->email) }}" required>
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('supplier.show', $supplier->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@stop
