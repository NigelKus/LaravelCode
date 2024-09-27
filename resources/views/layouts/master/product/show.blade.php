@extends('adminlte::page')

@section('title', 'Product Details')

@section('content_header')
    <div class = "d-flex justify-content-between align-items-center">
        <h1>Product Details</h1>
        <form action="{{ route('product.destroy', $product->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <h4 >Product Information</h4>
            <dl class="row">
                <dt class="col-sm-3">Code</dt>
                <dd class="col-sm-9">{{ $product->code }}</dd>

                <dt class="col-sm-3">Collection</dt>
                <dd class="col-sm-9">{{ $product->collection }}</dd>

                <dt class="col-sm-3">Weight</dt>
                <dd class="col-sm-9">{{ $product->weight }} gr</dd>

                <dt class="col-sm-3">Price</dt>
                <dd class="col-sm-9">Rp {{ number_format($product->price, 2) }}</dd>

                <dt class="col-sm-3">Stock</dt>
                <dd class="col-sm-9">{{ $product->stock }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $product->description }}</dd>

                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">{{ ucfirst(str_replace('_', ' ', $product->status)) }}</dd>
            </dl>
        </div>

    <div card ="card-body">
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Change Product Status</h3>
            </div>
    
            <div class="card-body">
                <form action="{{ route('product.updateStatus', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to change the status?');">
                    @csrf
                    @method('POST')
    
                    <div class="form-group">
                        <label for="status">Select Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active" {{ $product->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="trashed" {{ $product->status == 'trashed' ? 'selected' : '' }}>Trashed</option>
                        </select>   
                    </div>
    
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>


    
            <div class="card-footer">
                <a href="{{ route('product.edit', $product->id) }}" class="btn btn-warning">Edit</a>
                <a href="{{ route('product.index') }}" class="btn btn-secondary">Back to List</a>
            </div>
        </div>
    </div>
@stop
