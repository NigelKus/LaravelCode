@extends('adminlte::page')

@section('title', 'Create Customer')

@section('content_header')
    <h1>Create Customer</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <div class="card-tools">
            <!-- Tools or additional buttons can be added here -->
        </div>
    </div>

    <div class="card-body">
        <form role="form"
            method="POST"
            action="{{ route('customer.store') }}"
            enctype="multipart/form-data"
            onsubmit="return confirm('Are you sure?')"
            class="form-horizontal">
            @csrf

            <!-- Display success message -->
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Display error message -->
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Name Field -->
            <div class="form-group">
                <label for="code">Code</label>
                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code') }}" required>
                @error('code')
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

            <!-- Sales Category Field (Dropdown) -->
            <div class="form-group">
                <label for="sales_category">Sales Category</label>
                <select class="form-control @error('sales_category') is-invalid @enderror" id="sales_category" name="sales_category">
                    <option value="">Select a Category</option>
                    <option value="Retail" {{ old('sales_category') == 'Retail' ? 'selected' : '' }}>Retail</option>
                    <option value="Wholesale" {{ old('sales_category') == 'Wholesale' ? 'selected' : '' }}>Wholesale</option>
                    <option value="Online" {{ old('sales_category') == 'Online' ? 'selected' : '' }}>Online</option>
                    <!-- Add more options as needed -->
                </select>
                @error('sales_category')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Address Field -->
            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}">
                @error('address')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Phone Field -->
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="number" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}" placeholder="1234567890">
                @error('phone')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Description Field -->
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Birth Date Field -->
            <div class="form-group">
                <label for="birth_date">Birth Date</label>
                <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" value="{{ old('birth_date') }}">
                @error('birth_date')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Birth City Field -->
            <div class="form-group">
                <label for="birth_city">Birth City</label>
                <input type="text" class="form-control @error('birth_city') is-invalid @enderror" id="birth_city" name="birth_city" value="{{ old('birth_city') }}">
                @error('birth_city')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Email Field -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Submit and Back Buttons -->
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('customer.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- /.card-body -->
    {{-- <div class="card-footer">
        AdminLTE
    </div> --}}
    <!-- /.card-footer -->
</div>
<!-- /.card -->
@endsection

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop
