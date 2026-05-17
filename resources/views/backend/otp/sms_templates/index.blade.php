@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-auto">
                <h1 class="h3">{{translate('SMS Templates')}}</h1>
            </div>
            <div class="col text-right">
                <a href="{{ route('sms-templates.create') }}" class="btn btn-primary btn-sm">
                    {{ translate('Add New Template') }}
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Identifier') }}</th>
                        <th>{{ translate('SMS Body') }}</th>
                        <th>{{ translate('Status') }}</th>
                        <th class="text-right">{{ translate('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sms_templates as $key => $template)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $template->identifier }}</td>
                            <td>{{ Str::limit($template->sms_body, 80) }}</td>
                            <td>{{ $template->status ? translate('Active') : translate('Inactive') }}</td>
                            <td class="text-right">
                                <a href="{{ route('sms-templates.edit', $template->id) }}" class="btn btn-soft-primary btn-icon btn-circle btn-sm">
                                    <i class="las la-edit"></i>
                                </a>
                                <form action="{{ route('sms-templates.destroy', $template->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete">
                                        <i class="las la-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $sms_templates->links() }}
            </div>
        </div>
    </div>
@endsection
