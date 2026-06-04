@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h1 class="h3">{{ translate('Store Promotions') }}</h1>
            <p class="text-muted mb-0">{{ translate('Arrange banner & content blocks top-to-bottom. Drag rows to reorder — the public') }}
                <a href="{{ route('promotions') }}" target="_blank">/promotions</a> {{ translate('page follows this order.') }}</p>
        </div>
        <div class="col-md-6 text-md-right">
            <a href="{{ route('store_promotions.settings') }}" class="btn btn-soft-info">
                <i class="las la-cog"></i> {{ translate('Page Settings') }}
            </a>
            <a href="{{ route('promotions') }}" target="_blank" class="btn btn-soft-secondary">
                <i class="las la-external-link-alt"></i> {{ translate('View Page') }}
            </a>
            <a href="{{ route('store_promotions.create') }}" class="btn btn-primary">
                <i class="las la-plus"></i> {{ translate('Add Block') }}
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Blocks') }} ({{ $promotions->total() }})</h5>
        <span class="ml-auto small text-muted" id="reorderStatus"></span>
        <form class="ml-3" id="search_form" action="" method="GET">
            <input type="text" class="form-control form-control-sm" id="search" name="search"
                @isset($sort_search) value="{{ $sort_search }}" @endisset
                placeholder="{{ translate('Search title / section') }}">
        </form>
    </div>
    <div class="card-body">
        <table class="table aiz-table mb-0">
            <thead>
                <tr>
                    <th style="width:36px;"></th>
                    <th>{{ translate('Preview') }}</th>
                    <th>{{ translate('Title / Type') }}</th>
                    <th>{{ translate('Badge') }}</th>
                    <th>{{ translate('Width') }}</th>
                    <th>{{ translate('Window') }}</th>
                    <th>{{ translate('Published') }}</th>
                    <th class="text-right">{{ translate('Options') }}</th>
                </tr>
            </thead>
            <tbody id="promoSortable">
                @forelse($promotions as $promo)
                    <tr data-id="{{ $promo->id }}">
                        <td class="drag-handle text-muted" title="{{ translate('Drag to reorder') }}" style="cursor:grab;">
                            <i class="las la-grip-vertical la-lg"></i>
                        </td>
                        <td>
                            @if($promo->type === 'content')
                                <span class="badge badge-soft-info"><i class="las la-align-left"></i> {{ translate('Content') }}</span>
                            @else
                                <img src="{{ uploaded_asset($promo->image) }}" alt="promo" class="size-60px img-fit rounded border">
                            @endif
                        </td>
                        <td>
                            <span class="fw-600">{{ $promo->title ?: ($promo->type === 'content' ? translate('Content block') : '—') }}</span>
                            @if($promo->featured)<span class="badge badge-inline badge-warning ml-1">{{ translate('Featured') }}</span>@endif
                            <div class="text-muted small">{{ ucfirst($promo->type) }}@if($promo->section) · {{ $promo->section }}@endif</div>
                            @if($promo->link_url)<div class="text-muted small text-truncate" style="max-width:220px;">{{ $promo->link_url }}</div>@endif
                        </td>
                        <td>{{ $promo->subtitle ?: '—' }}</td>
                        <td><span class="badge badge-soft-secondary">{{ ucfirst($promo->width) }}</span></td>
                        <td class="small text-muted">
                            {{ optional($promo->starts_at)->format('M d') ?: '—' }}
                            &rarr; {{ optional($promo->ends_at)->format('M d, Y') ?: translate('—') }}
                        </td>
                        <td>
                            <label class="aiz-switch aiz-switch-success mb-0">
                                <input onchange="updatePromoStatus(this)" value="{{ $promo->id }}" type="checkbox" @if($promo->published) checked @endif>
                                <span class="slider round"></span>
                            </label>
                        </td>
                        <td class="text-right text-nowrap">
                            <a class="btn btn-soft-primary btn-icon btn-circle btn-sm" href="{{ route('store_promotions.edit', $promo->id) }}" title="{{ translate('Edit') }}">
                                <i class="las la-edit"></i>
                            </a>
                            <a class="btn btn-soft-info btn-icon btn-circle btn-sm" href="{{ route('store_promotions.duplicate', $promo->id) }}" title="{{ translate('Duplicate') }}">
                                <i class="las la-copy"></i>
                            </a>
                            <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete" data-href="{{ route('store_promotions.destroy', $promo->id) }}" title="{{ translate('Delete') }}">
                                <i class="las la-trash"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ translate('No blocks yet. Click "Add Block" to build your promotions page.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($promotions->hasPages())
            <div class="alert alert-soft-warning small mt-3 mb-0">
                {{ translate('Drag-and-drop ordering works within a page. With many blocks, set the order field and keep them on one page for full control.') }}
            </div>
        @endif
        <div class="aiz-pagination mt-3">{{ $promotions->appends(request()->input())->links() }}</div>
    </div>
</div>

@endsection

@section('modal')
    @include('modals.delete_modal')
@endsection

@section('style')
<style>
    #promoSortable tr.dragging { opacity:.4; }
    #promoSortable tr.drag-over { border-top:2px solid #4e73df; }
</style>
@endsection

@section('script')
<script type="text/javascript">
    function updatePromoStatus(el){
        $.post('{{ route('store_promotions.update_status') }}', {_token:'{{ csrf_token() }}', id:el.value, status: el.checked ? 1 : 0}, function(data){
            if(data == 1){ AIZ.plugins.notify('success', '{{ translate('Status updated') }}'); }
            else { AIZ.plugins.notify('danger', '{{ translate('Something went wrong') }}'); el.checked = !el.checked; }
        });
    }

    // ── Native drag-and-drop reordering ──────────────────────────────────────
    (function(){
        var tbody = document.getElementById('promoSortable');
        if(!tbody) return;
        var dragRow = null;

        tbody.querySelectorAll('tr[data-id]').forEach(function(row){
            row.setAttribute('draggable', 'true');

            row.addEventListener('dragstart', function(e){
                dragRow = row; row.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            row.addEventListener('dragend', function(){
                row.classList.remove('dragging');
                tbody.querySelectorAll('tr').forEach(function(r){ r.classList.remove('drag-over'); });
            });
            row.addEventListener('dragover', function(e){
                e.preventDefault();
                if(!dragRow || dragRow === row) return;
                var rect = row.getBoundingClientRect();
                var after = (e.clientY - rect.top) > rect.height / 2;
                tbody.querySelectorAll('tr').forEach(function(r){ r.classList.remove('drag-over'); });
                row.classList.add('drag-over');
                tbody.insertBefore(dragRow, after ? row.nextSibling : row);
            });
            row.addEventListener('drop', function(e){ e.preventDefault(); persistOrder(); });
        });

        function persistOrder(){
            var ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(r){ return r.getAttribute('data-id'); });
            var status = document.getElementById('reorderStatus');
            status.textContent = '{{ translate('Saving order…') }}';
            $.post('{{ route('store_promotions.reorder') }}', {_token:'{{ csrf_token() }}', ids: ids}, function(res){
                status.textContent = res.success ? '{{ translate('Order saved') }} ✓' : '';
                setTimeout(function(){ status.textContent=''; }, 1800);
            }).fail(function(){ status.textContent=''; AIZ.plugins.notify('danger', '{{ translate('Could not save order') }}'); });
        }
    })();
</script>
@endsection
