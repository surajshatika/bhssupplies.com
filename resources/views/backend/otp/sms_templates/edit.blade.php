@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <h1 class="h3">{{translate('Edit SMS Template')}}</h1>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('sms-templates.update', $smsTemplate->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group row">
                            <div class="col-lg-3">
                                <label class="col-from-label">{{ translate('Identifier') }}</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="identifier" value="{{ $smsTemplate->identifier }}" required>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-3">
                                <label class="col-from-label">{{ translate('SMS Body') }}</label>
                            </div>
                            <div class="col-md-9">
                                <textarea class="form-control" name="sms_body" rows="5" required>{{ $smsTemplate->sms_body }}</textarea>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-3">
                                <label class="col-from-label">{{ translate('Template ID') }}</label>
                            </div>
                            <div class="col-md-9">
                                <input type="text" class="form-control" name="template_id" value="{{ $smsTemplate->template_id }}">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-3">
                                <label class="col-from-label">{{ translate('Status') }}</label>
                            </div>
                            <div class="col-md-9">
                                <select class="form-control" name="status">
                                    <option value="1" {{ $smsTemplate->status == 1 ? 'selected' : '' }}>{{ translate('Active') }}</option>
                                    <option value="0" {{ $smsTemplate->status == 0 ? 'selected' : '' }}>{{ translate('Inactive') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{ translate('Update') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
