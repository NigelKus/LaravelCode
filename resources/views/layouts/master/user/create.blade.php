@extends('adminlte::page')

@section('title', 'Create User')

@section('content_header')
    <h1>Create User</h1>
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
            action="{{ route('user.store') }}"
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
                <label for="role">Role</label>
                <select class="form-control @error('role') is-invalid @enderror" id="role" name="role">
                    <option value="">Select a Role</option>
                    <option value="Finance 1" {{ old('role') == 'Finance 1' ? 'selected' : '' }}>Finance 1</option>
                    <option value="Finance 2" {{ old('role') == 'Finance 2' ? 'selected' : '' }}>Finance 2</option>
                    <option value="Finance 3" {{ old('role') == 'Finance 3' ? 'selected' : '' }}>Finance 3</option>
                    <option value="Accountant" {{ old('role') == 'Accountant' ? 'selected' : '' }}>Accountant</option>
                    <!-- Add more options as needed -->
                </select>
                @error('role')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="office_id">Office</label>
                <select class="form-control @error('office_id') is-invalid @enderror" id="office_id" name="office_id" required>
                    <option value="">Select an Office</option>
                    @foreach($offices as $office)
                        <option value="{{ $office->id }}" {{ old('office_id') == $office->id ? 'selected' : '' }}>
                            {{ $office->name }} ({{ $office->location }})
                        </option>
                    @endforeach
                </select>
                @error('office_id')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Address Field -->
            <div class="form-group">
                <label for="address">Email</label>
                <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Address Field -->
            <div class="form-group">
                <label for="address">Phone</label>
                <input type="number" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                @error('phone')
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

            <div class="form-group">
                <label for="address">Birth Location</label>
                <input type="text" class="form-control @error('birth_location') is-invalid @enderror" id="birth_location" name="birth_location" value="{{ old('birth_location') }}">
                @error('birth_location')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}">
                @error('address')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" value="{{ old('password') }}">
                @error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm Password</label>
                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" value="{{ old('password_confirmation') }}">
                @error('password_confirmation')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('user.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

