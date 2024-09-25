<!-- layouts/transactional/sales_invoice/partials/product_line_edit.blade.php -->
<tr class="product-line" id="product-line-{{ $product_id }}">
    <td>
        {{ $product->code ?? 'N/A' }} - {{ $product->collection ?? 'N/A' }} - {{ $product->weight ?? 'N/A' }}
    </td>
    <td>
        <input type="number" name="requested[]" class="form-control requested" value="{{ $requested }}" min="0" />
    </td>
    <td>
        <input type="number" name="qtys[]" class="form-control quantity" value="{{ $qty }}" min="0" readonly />
    </td>
    <td>
        <input type="text" name="price_eachs[]" class="form-control price-each" value="{{ $price }}" readonly />
    </td>
    <td>
        <input type="text" name="price_totals[]" class="form-control price-total" value="{{ $price_total }}" readonly />
    </td>
    <td>
        <input type="number" class="form-control quantity_sent" value="{{ $quantity_sent  }}" readonly />
    </td>
    <td>
        
        <button type="button" class="btn btn-danger btn-sm del-row">
            Remove
        </button>
    </td>
    <input type="hidden" name="product_id[]" value="{{ $product_id }}">
    <input type="hidden" name="salesdetail_id[]" value="{{ $salesorderdetail_id }}">
    <input type="hidden" name="sales_order_id[]" value="{{ $sales_order_id }}">
</tr>
