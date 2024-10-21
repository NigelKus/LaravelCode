<tr class="product-line" id="product-line-{{ $product_id }}">
    <td>
        <!-- Display error message for product selection -->
        <select name="product_ids[]" class="select-product form-control @error('product_ids.*') is-invalid @enderror" data-product-id="{{ $product_id }}">
            <option value="">Select Product</option>
            @foreach($products as $id => $product)
                <option value="{{ $product->id }}" data-price="{{ $product['price'] }}" {{ $product_id == $id ? 'selected' : '' }}>
                    {{ $product['code'] }} - {{ $product['collection'] }} - {{ $product['weight'] }}
                </option>
            @endforeach
        </select>
        <!-- Display error message for product selection -->
        @error('product_ids.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    <td>
        <!-- Display error message for quantity input -->
        <input type="number" name="qtys[]" class="form-control quantity @error('qtys.*') is-invalid @enderror" value="{{ $qty }}" min="1" />
        <!-- Display error message for quantity input -->
        @error('qtys.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    <td>
        <input type="text" name="price_eachs[]" class="form-control price-each" value="{{ $price }}"  />
    </td>
    <td>
        <input type="text" name="price_totals[]" class="form-control price-total" value="{{ $price_total }}" readonly />
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm del-row">
            Remove
        </button>
    </td>
</tr>
