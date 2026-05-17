<form action="{{ route('performance_optimizer.settings.update') }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-header"><h5 class="h6 mb-0">{{ translate('Global Settings') }}</h5></div>
        <div class="card-body row">
            <div class="col-md-6">
                <div class="form-group d-flex justify-content-between align-items-center">
                    <label class="mb-0">{{ translate('Master switch') }}</label>
                    <label class="aiz-switch aiz-switch-success mb-0">
                        <input type="checkbox" name="perf_status" value="1" @if(get_setting('perf_status') == 1) checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary btn-sm">{{ translate('Save settings') }}</button>
        </div>
    </div>
</form>
