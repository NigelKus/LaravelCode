<tr class="invoice-line">
    <td>
        <div class="form-group">
            <input type="hidden" name="invoice_id[]" value="{{ $invoice_id ?? '' }}" required>
            @error('invoice_id.*')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
            <span class="invoice-code">{{ $invoice_code ?? '' }}</span> <!-- Display the invoice code -->
        </div>
    </td>
    <td>
        <!-- Payment Requested -->
        <input type="number" name="requested[]" class="form-control payment @error('requested.*') is-invalid @enderror"
               value="{{ $requested ?? '' }}" min="0" />
        @error('requested.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    
    <td>
        <!-- Original Price -->
        <input type="number" name="original_prices[]" class="form-control original-price"
               value="{{ $original_price }}" readonly />
    </td>
    
    <td>
        <!-- Remaining Price -->
        <input type="number" name="remaining_prices[]" class="form-control remaining_price"
               value="{{ $remaining_price }}" readonly />
    </td>
    
    <td>
        <!-- Action Button: Remove Line -->
        <button type="button" class="btn btn-danger btn-sm del-invoice-line">
            Remove
        </button>
    </td>
</tr>
