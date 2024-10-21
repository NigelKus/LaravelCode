@extends('adminlte::page')

@section('title', 'Edit Product')

@section('content_header')
    <h1>Edit Product</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Product Information</h3>
        </div>

        <div class="card-body">
            <!-- Display validation errors if any -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('product.update', $product->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Code Field -->
                <div class="form-group">
                    <label for="code">Code</label>
                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $product->code) }}" required>
                    @error('code')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Collection Field -->
                <div class="form-group">
                    <label for="collection">Collection</label>
                    <select class="form-control @error('collection') is-invalid @enderror" id="collection" name="collection" required>
                        <option value="">Select Collection</option>
                        <option value="Waris Classic Edition" {{ old('collection', $product->collection) == 'Waris Classic Edition' ? 'selected' : '' }}>Waris Classic Edition</option>
                        <option value="Waris Special Dragon Edition" {{ old('collection', $product->collection) == 'Waris Special Dragon Edition' ? 'selected' : '' }}>Waris Special Dragon Edition</option>
                        <option value="Waris Special Eid Edition" {{ old('collection', $product->collection) == 'Waris Special Eid Edition' ? 'selected' : '' }}>Waris Special Eid Edition</option>
                    </select>
                    @error('collection')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Weight Field -->
                <div class="form-group">
                    <label for="weight">Weight (kg)</label>
                    <input type="number" step="0.01" class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" value="{{ old('weight', $product->weight) }}" required>
                    @error('weight')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Price Field -->
                <div class="form-group">
                    <label for="price">Price (Rp)</label>
                    <input type="number" step="0.01" class="form-control @error('price') is-invalid @enderror" id="price" name="price" value="{{ old('price', $product->price) }}" required>
                    @error('price')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Stock Field -->
                <div class="form-group">
                    <label for="stock">Stock</label>
                    <input type="number" class="form-control @error('stock') is-invalid @enderror" id="stock" name="stock" value="{{ old('stock', $product->stock) }}" min="0" required>
                    @error('stock')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Description Field -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary">Update Product</button>
                <a href="{{ route('product.index') }}" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
@stop
