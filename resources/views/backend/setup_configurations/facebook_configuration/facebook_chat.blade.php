@extends('backend.layouts.app')

@section('content')
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{translate('Facebook Chat Setting')}}</h5>
                </div>
                <div class="card-body">
                    <form class="form-horizontal" action="{{ route('facebook_chat.update') }}" method="POST">
                        @csrf
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="col-from-label">{{translate('Facebook Chat')}}</label>
                            </div>
                            <div class="col-md-7">
                                <label class="aiz-switch aiz-switch-success mb-0">
                                    <input value="1" name="facebook_chat" type="checkbox" @if (get_setting('facebook_chat') == 1) checked @endif>
                                    <span class="slider round"></span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="col-from-label">{{translate('Facebook Page ID')}}</label>
                            </div>
                            <div class="col-md-7">
                                <input type="text" class="form-control" name="facebook_page_id" value="{{ get_setting('facebook_page_id') }}" placeholder="{{ translate('Facebook Page ID') }}">
                            </div>
                        </div>
                        <div class="form-group mb-0 text-right">
                            <button type="submit" class="btn btn-sm btn-primary">{{translate('Save')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
