<tr>
    <td>
        <span style="padding-left: {{ $depth * 20 }}px">
            @if($depth > 0) <i class="las la-level-up-alt text-muted" style="transform: rotate(90deg)"></i> @endif
            {{ $category->name }}
        </span>
    </td>
    <td>
        @php $mapping = \App\Models\AmazonCategoryMapping::where('website_category_id', $category->id)->first(); @endphp
        <input type="text" name="mappings[{{ $category->id }}][amazon_category_id]"
               class="form-control form-control-sm"
               value="{{ $mapping->amazon_category_id ?? '' }}"
               placeholder="e.g. 3375251">
    </td>
    <td>
        <input type="text" name="mappings[{{ $category->id }}][amazon_category_name]"
               class="form-control form-control-sm"
               value="{{ $mapping->amazon_category_name ?? '' }}"
               placeholder="e.g. HVAC Parts">
    </td>
    <td>
        <input type="text" name="mappings[{{ $category->id }}][amazon_product_type]"
               class="form-control form-control-sm"
               value="{{ $mapping->amazon_product_type ?? '' }}"
               placeholder="e.g. HVAC_PART">
    </td>
</tr>
