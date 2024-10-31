@extends('adminlte::page')

@section('title', 'Edit User')

@section('content_header')
    <h1>Edit User</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit User Information</h3>
        </div>
        <form method="POST" action="{{ route('user.update', $user->id) }}" class="form-horizontal">
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
                    <label for="name">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name)}}" required>
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
                        <option value="Finance 1" {{ old('role', $user->role) == 'Finance 1' ? 'selected' : '' }}>Finance 1</option>
                        <option value="Finance 2" {{ old('role', $user->role) == 'Finance 2' ? 'selected' : '' }}>Finance 2</option>
                        <option value="Finance 3" {{ old('role', $user->role) == 'Finance 3' ? 'selected' : '' }}>Finance 3</option>
                        <option value="Accountant" {{ old('role', $user->role) == 'Accountant' ? 'selected' : '' }}>Accountant</option>
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
                            <option value="{{ $office->id }}" {{ old('office_id', $user->office_id) == $office->id ? 'selected' : '' }}>
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
                    <input type="text" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}">
                    @error('email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
    
                <!-- Address Field -->
                <div class="form-group">
                    <label for="address">Phone</label>
                    <input type="number" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                    @error('phone')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
    
                <!-- Birth Date Field -->
                <div class="form-group">
                    <label for="birth_date">Birth Date</label>
                    <input type="date" class="form-control @error('birth_date') is-invalid @enderror" id="birth_date" name="birth_date" value="{{ old('birth_date', $user->birth_date) }}">
                    @error('birth_date')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
    
                <div class="form-group">
                    <label for="address">Birth Location</label>
                    <input type="text" class="form-control @error('birth_location') is-invalid @enderror" id="birth_location" name="birth_location" value="{{ old('birth_location', $user->birth_location) }}">
                    @error('birth_location')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
    
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address', $user->address) }}">
                    @error('address')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" value="{{ old('password') }}" >
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

            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('user.show', $user->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@stop