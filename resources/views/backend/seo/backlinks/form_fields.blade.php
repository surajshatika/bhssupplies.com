<div class="form-group"><label>{{ translate('Site name') }} *</label>
    <input name="site_name" class="form-control" required value="{{ $v('site_name') }}"></div>
<div class="form-row">
    <div class="form-group col"><label>{{ translate('Type') }}</label>
        <select name="type" class="form-control">
            @foreach(\App\Models\SeoBacklink::TYPES as $t)<option value="{{ $t }}" @selected(($v('type') ?? 'other') === $t)>{{ ucfirst($t) }}</option>@endforeach
        </select></div>
    <div class="form-group col"><label>{{ translate('Status') }}</label>
        <select name="status" class="form-control">
            @foreach(\App\Models\SeoBacklink::STATUSES as $s)<option value="{{ $s }}" @selected(($v('status') ?? 'prospect') === $s)>{{ ucfirst($s) }}</option>@endforeach
        </select></div>
</div>
<div class="form-group"><label>{{ translate('Page that links to us') }} <small class="text-muted">({{ translate('verified automatically') }})</small></label>
    <input name="source_url" type="url" class="form-control" placeholder="https://..." value="{{ $v('source_url') }}"></div>
<div class="form-group"><label>{{ translate('Sign-up / pitch URL') }}</label>
    <input name="submit_url" type="url" class="form-control" placeholder="https://..." value="{{ $v('submit_url') }}"></div>
<div class="form-group"><label>{{ translate('Our page it should link to') }}</label>
    <input name="target_url" type="url" class="form-control" placeholder="https://www.bhssupplies.com/..." value="{{ $v('target_url') }}"></div>
<div class="form-row">
    <div class="form-group col"><label>{{ translate('Anchor text') }}</label><input name="anchor_text" class="form-control" value="{{ $v('anchor_text') }}"></div>
    <div class="form-group col"><label>{{ translate('Contact') }}</label><input name="contact" class="form-control" value="{{ $v('contact') }}"></div>
</div>
<div class="form-group mb-0"><label>{{ translate('Notes') }}</label><textarea name="notes" rows="2" class="form-control">{{ $v('notes') }}</textarea></div>
