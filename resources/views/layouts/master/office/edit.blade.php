@extends('adminlte::page')

@section('title', 'Edit Office')

@section('content_header')
    <h1>Edit Office</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Office Information</h3>
        </div>
        <form method="POST" action="{{ route('office.update', $office->id) }}" class="form-horizontal">
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
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $office->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
    
                <!-- Name Field -->
                <div class="form-group">
                    <label for="code">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $office->code) }}" required>
                    @error('code')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
    
    
                <!-- Address Field -->
                <div class="form-group">
                    <label for="address">Location</label>
                    <input type="text" class="form-control @error('location') is-invalid @enderror" id="location" name="location" value="{{ old('location', $office->location) }}">
                    @error('location')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
    
                <!-- Address Field -->
                <div class="form-group">
                    <label for="address">Phone</label>
                    <input type="number" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $office->phone) }}">
                    @error('phone')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
    
                <!-- Birth Date Field -->
                <div class="form-group">
                    <label for="opening_date">Opening Date</label>
                    <input type="date" class="form-control @error('opening_date') is-invalid @enderror" id="opening_date" name="opening_date" value="{{ old('opening_date', $office->opening_date) }}">
                    @error('opening_date')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('office.show', $office->id) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
@stop