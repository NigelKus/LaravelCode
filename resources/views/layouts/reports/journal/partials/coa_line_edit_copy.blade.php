<tr class="coa-line" id="coa-line-{{ $coa_id }}">
    <td>
        <select name="coa_ids1[]" class="select-coa form-control @error('coa_ids.*') is-invalid @enderror" data-coa-id="{{ $coa_id }}" >
            <option value="">Select Chart of Account</option>
            @foreach($CoAs as $coa)
                <option value="{{ $coa->code }}" {{ $coa_id == $coa->id ? 'selected' : '' }}>
                    {{ $coa->name }} - {{ $coa->code }}
                </option>
            @endforeach
        </select>
        @error('coa_ids.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    <td>
        <input type="number" name="amounts1[]" class="form-control quantity @error('amounts.*') is-invalid @enderror" value="{{old('amounts.' . $index, $amount) }}" min="1"  />
        @error('amounts.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    <td>
        <input type="text" name="descriptions1[]" class="form-control quantity @error('descriptions.*') is-invalid @enderror" value="{{ old('descriptions.' . $index, $description) }}"  />
        @error('descriptions.*')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
        @enderror
    </td>
    <td>
        <button type="button" class="btn btn-danger btn-sm del-row" onclick="removeRow(this)">
            Remove
        </button>
    </td>
</tr>
