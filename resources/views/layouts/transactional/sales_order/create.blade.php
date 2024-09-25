@extends('adminlte::page')

@section('title', 'Create Sales Order')

@section('content_header')
    <h1>Create Sales Order</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <!-- Sales Order Form -->
            <form method="POST" action="{{ route('sales_order.store') }}" class="form-horizontal">
                @csrf

                <!-- Customer Dropdown -->
                <div class="form-group">
                    <label for="customer_id">Customer</label>
                    <select class="form-control @error('customer_id') is-invalid @enderror" id="customer_id" name="customer_id" required>
                        <option value="">Select a Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Description Field -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Order Date Field -->
                <div class="form-group">
                    <label for="date">Order Date</label>
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" readonly>
                    @error('date')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <!-- Products Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Products</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="product">Select Product</label>
                            <select class="form-control" id="product" name="product">
                                <option value="">Select a Product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" data-code="{{ $product->code }}" data-collection="{{ $product->collection }}" data-weight="{{ $product->weight }}" data-price="{{ $product->price }}">
                                        {{ $product->code }} - {{ $product->collection }} - {{ $product->weight }} g
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary" id="add-product">Add</button>

                        <!-- Products Table -->
                        <table class="table table-bordered mt-3" id="products-table">
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Collection</th>
                                    <th>Weight</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>

                        <!-- Hidden input to collect products data -->
                        <input type="hidden" id="products-data" name="products">
                    </div>
                </div>

                <!-- Form Submit and Cancel Buttons -->
                <div class="form-group mt-3">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('sales_order.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    @push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
    @endpush

    @push('js')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const addButton = document.getElementById('add-product');
            const productSelect = document.getElementById('product');
            const productsTableBody = document.getElementById('products-table').querySelector('tbody');
            const productsDataInput = document.getElementById('products-data');

            // Initialize Select2
            $(productSelect).select2({
                placeholder: 'Select a Product',
                allowClear: true
            });

            addButton.addEventListener('click', function () {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                if (!selectedOption || selectedOption.value === "") return;

                const code = selectedOption.getAttribute('data-code');
                const collection = selectedOption.getAttribute('data-collection');
                const weight = selectedOption.getAttribute('data-weight');
                const price = selectedOption.getAttribute('data-price');
                const productId = selectedOption.value;

                // Check if the product is already in the table
                if ([...productsTableBody.querySelectorAll('tr')].some(row => row.dataset.productId === productId)) {
                    alert('Product is already added.');
                    return;
                }

                const row = document.createElement('tr');
                row.dataset.productId = productId;

                row.innerHTML = `
                    <td>${code}</td>
                    <td>${collection}</td>
                    <td>${weight} g</td>
                    <td>${price}</td>
                    <td><input type="number" class="form-control quantity" value="1" min="1"></td>
                    <td class="total">${price}</td>
                    <td><button type="button" class="btn btn-danger remove-product">Remove</button></td>
                `;

                row.dataset.product = JSON.stringify({
                    product_id: productId,
                    quantity: 1,
                    price: parseFloat(price)
                });

                productsTableBody.appendChild(row);
                updateTotals();
                updateProductsData();
            });

            productsTableBody.addEventListener('change', function (e) {
                if (e.target.classList.contains('quantity')) {
                    updateTotals();
                    updateProductsData();
                }
            });

            productsTableBody.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-product')) {
                    e.target.closest('tr').remove();
                    updateTotals();
                    updateProductsData();
                }
            });

            function updateTotals() {
                const rows = productsTableBody.querySelectorAll('tr');
                rows.forEach(row => {
                    const price = parseFloat(row.children[3].textContent);
                    const quantity = parseFloat(row.querySelector('.quantity').value);
                    const totalCell = row.querySelector('.total');
                    totalCell.textContent = (price * quantity).toFixed(2);

                    const productData = JSON.parse(row.dataset.product);
                    productData.quantity = quantity;
                    row.dataset.product = JSON.stringify(productData);
                });
            }


            function updateProductsData() {
                const rows = productsTableBody.querySelectorAll('tr');
                const products = [];
                rows.forEach(row => {
                    const productData = JSON.parse(row.dataset.product);
                    productData.quantity = parseFloat(row.querySelector('.quantity').value);
                    products.push(productData);
                });
                productsDataInput.value = JSON.stringify(products);
            }
        });
    </script>
    @endpush
@stop
