@extends('adminlte::page')

@section('title', 'Product Index')

@section('content_header')
    <h1>Product</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Product List</h3>

            <!-- Filter Form -->
            <div class="card-tools">
                <a href="{{ route('product.create') }}" class="btn btn-success btn-sm ml-2">
                    <i class="fas fa-plus"></i> Create
                </a>
                </form>
            </div>
        </div>

        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>
                            <!-- Add the dropdown filter in the table header -->
                            <form method="GET" action="{{ route('product.index') }}" class="d-inline">
                                <select class="form-control form-control-sm" id="collection-filter" name="collection" onchange="this.form.submit()">
                                    <option value="">All Collections</option>
                                    @foreach($collections as $collection)
                                        <option value="{{ $collection }}" {{ request('collection') == $collection ? 'selected' : '' }}>
                                            {{ $collection }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        </th>
                        <th>Weight</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>
                            <form method="GET" action="{{ route('product.index') }}" style="display:inline;">
                                <select class="form-control" id="status-filter" name="status" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="trashed" {{ request('status') == 'trashed' ? 'selected' : '' }}>Trashed</option>
                                </select>
                            </form>

                        </th>
                        <th>
                            Actions
                            <!-- Optional: Dropdown filter can also be placed here if needed -->
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>{{ $product->code }}</td>
                        <td>{{ $product->collection }}</td>
                        <td>{{ $product->weight }}</td>
                        <td>{{ number_format($product->price, 0, ',', '.') }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>{{ ucfirst($product->status) }}</td>
                        <td>
                            <a href="{{ route('product.show', $product->id) }}" class="btn btn-info btn-sm">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                
            </table>
        </div>
    </div>
@stop
