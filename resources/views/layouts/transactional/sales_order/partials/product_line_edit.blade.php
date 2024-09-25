<tr class="product-line" id="product-line-{{ $product_id }}">
    <td>
        <select name="product_ids[]" class="select-product form-control" data-product-id="{{ $product_id }}">
            <option value="">Select Product</option>
            @foreach($products as $id => $product)
                <option value="{{ $product->id }}" data-price="{{ $product['price'] }}" 
                    {{ $product->id == $product_id ? 'selected' : '' }}>
                    {{ $product['code'] }} - {{ $product['collection'] }} - {{ $product['weight'] }} 
                </option>
            @endforeach
        </select>
        @error('product_ids.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    <td>
        <input type="number" name="qtys[]" class="form-control quantity" value="{{ $qty }}" min="1" />
        @error('qtys.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    <td>
        <input type="text" name="quantity_remaining[]" class="form-control quantity-remaining" value="{{ $quantity_remaining }}" readonly />
    </td>
    <td>
        <input type="text" name="price_eachs[]" class="form-control price-each" value="{{ $price }}" readonly />
    </td>
    <td>
        <input type="text" name="price_totals[]" class="form-control price-total" value="{{ $price_total }}" readonly />
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm del-row">Remove</button>
    </td>
</tr>
