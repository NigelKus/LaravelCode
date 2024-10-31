@extends('adminlte::page')

@section('title', 'Create Office')

@section('content_header')
    <h1>Create Office</h1>
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
            action="{{ route('office.store') }}"
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


            <!-- Address Field -->
            <div class="form-group">
                <label for="address">Location</label>
                <input type="text" class="form-control @error('location') is-invalid @enderror" id="location" name="location" value="{{ old('location') }}">
                @error('location')
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
                <label for="opening_date">Opening Date</label>
                <input type="date" class="form-control @error('opening_date') is-invalid @enderror" id="opening_date" name="opening_date" value="{{ old('opening_date') }}">
                @error('opening_date')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('office.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

