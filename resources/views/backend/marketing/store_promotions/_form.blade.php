@php $p = $promotion ?? null; $type = old('type', $p->type ?? 'banner'); @endphp

<div class="form-group">
    <label class="d-block">{{ translate('Block Type') }}</label>
    <label class="mr-3"><input type="radio" name="type" value="banner" class="promo-type" @if($type==='banner') checked @endif> {{ translate('Banner image (sale flyer)') }}</label>
    <label><input type="radio" name="type" value="content" class="promo-type" @if($type==='content') checked @endif> {{ translate('Content block (rich text)') }}</label>
</div>

<div class="form-group">
    <label>{{ translate('Title') }} <small class="text-muted">({{ translate('optional, for admin reference / alt text') }})</small></label>
    <input type="text" name="title" class="form-control" value="{{ old('title', $p->title ?? '') }}" placeholder="{{ translate('e.g. Liquidation Sale - Vanity') }}">
</div>

{{-- ── Banner fields ── --}}
<div class="promo-banner-fields" @if($type!=='banner') style="display:none" @endif>
    <div class="form-group">
        <label>{{ translate('Promotion Image') }} <span class="text-danger">*</span> <small class="text-muted">({{ translate('any size; square ~1080px or wide banners both work') }})</small></label>
        @if(!empty($p->image))
            <div class="mb-2"><img src="{{ uploaded_asset($p->image) }}" class="img-fit rounded border" style="max-height:160px;" alt="current"></div>
        @endif
        <div class="input-group" data-toggle="aizuploader" data-type="image">
            <div class="input-group-prepend"><div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div></div>
            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
            <input type="hidden" name="image" class="selected-files" value="{{ old('image', $p->image ?? '') }}">
        </div>
        <div class="file-preview box sm"></div>
    </div>
    <div class="form-group">
        <label>{{ translate('Badge text') }} <small class="text-muted">({{ translate('optional overlay, e.g.') }} "UP TO 80% OFF")</small></label>
        <input type="text" name="subtitle" class="form-control" value="{{ old('subtitle', $p->subtitle ?? '') }}" placeholder="UP TO 80% OFF">
    </div>
    <div class="form-group">
        <label>{{ translate('Click-through URL') }} <small class="text-muted">({{ translate('optional') }})</small></label>
        <input type="text" name="link_url" class="form-control" value="{{ old('link_url', $p->link_url ?? '') }}" placeholder="https://www.bhssupplies.com/category/...">
    </div>
</div>

{{-- ── Content fields ── --}}
<div class="promo-content-fields" @if($type!=='content') style="display:none" @endif>
    <div class="form-group">
        <label>{{ translate('Content (rich text / HTML)') }} <span class="text-danger">*</span></label>
        <textarea name="content" class="form-control aiz-text-editor" rows="6">{{ old('content', $p->content ?? '') }}</textarea>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-4">
        <label>{{ translate('Width') }}</label>
        <select name="width" class="form-control">
            @foreach(['full'=>'Full width','half'=>'Half (2 per row)','third'=>'Third (3 per row)'] as $val=>$lbl)
                <option value="{{ $val }}" @if(old('width', $p->width ?? 'full')===$val) selected @endif>{{ translate($lbl) }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-4">
        <label>{{ translate('Section label') }} <small class="text-muted">({{ translate('optional') }})</small></label>
        <input type="text" name="section" class="form-control" value="{{ old('section', $p->section ?? '') }}" placeholder="HVAC / Tools / Bathroom">
    </div>
    <div class="form-group col-md-4">
        <label>{{ translate('Order') }}</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $p->sort_order ?? 0) }}">
        <small class="text-muted">{{ translate('Lower = higher up. You can also drag rows on the list.') }}</small>
    </div>
</div>

<div class="form-row">
    <div class="form-group col-md-6">
        <label>{{ translate('Start date') }} <small class="text-muted">({{ translate('optional') }})</small></label>
        <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at', optional($p->starts_at ?? null)->format('Y-m-d')) }}">
    </div>
    <div class="form-group col-md-6">
        <label>{{ translate('End date') }} <small class="text-muted">({{ translate('optional — auto-hides after this date') }})</small></label>
        <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at', optional($p->ends_at ?? null)->format('Y-m-d')) }}">
    </div>
</div>

<div class="form-row">
    <div class="col-md-6">
        <label class="aiz-switch aiz-switch-success">
            <input type="checkbox" name="published" value="1" @if(old('published', $p->published ?? true)) checked @endif>
            <span></span> {{ translate('Published') }}
        </label>
    </div>
    <div class="col-md-6">
        <label class="aiz-switch aiz-switch-warning">
            <input type="checkbox" name="featured" value="1" @if(old('featured', $p->featured ?? false)) checked @endif>
            <span></span> {{ translate('Featured (highlight)') }}
        </label>
    </div>
</div>

<script type="text/javascript">
    (function(){
        function sync(){
            var t = document.querySelector('.promo-type:checked');
            var isContent = t && t.value === 'content';
            document.querySelectorAll('.promo-banner-fields').forEach(function(el){ el.style.display = isContent ? 'none' : ''; });
            document.querySelectorAll('.promo-content-fields').forEach(function(el){ el.style.display = isContent ? '' : 'none'; });
        }
        document.querySelectorAll('.promo-type').forEach(function(r){ r.addEventListener('change', sync); });
        sync();
    })();
</script>
