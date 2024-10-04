<tr class="invoice-line">
    <td>
        <div class="form-group">
            <input type="hidden" name="invoice_id[]"  required>
            @error('invoice_id.*')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    </td>
    <td>
        <!-- Payment Requested -->
        <input type="number" name="requested[]" class="form-control payment @error('requested.*') is-invalid @enderror" min="0" />
        @error('requested.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    
    <td>
        <!-- Original Price -->
        <input type="text" name="original_prices[]" class="form-control original-price" readonly />
    </td>

    <td>
        <!-- Remaining Price -->
        <input type="text" name="remaining_prices[]" class="form-control remaining_price" readonly />
    </td>
    
    <td>
        <!-- Action Button: Remove Line -->
        <button type="button" class="btn btn-danger btn-sm del-invoice-line">
            Remove
        </button>
    </td>
</tr>
