@extends('layouts.admin')

@section('title', __('ui.workflow.count_page_title'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('super.stock-opnames.index') }}">{{ __('ui.common.stock_opname') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.workflow.count_page_heading') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h4 class="page-title mb-1">{{ __('ui.workflow.count_page_heading') }}</h4><span class="text-muted">{{ $stockOpname->number }} · {{ __('ui.workflow.system_balance_snapshot') }} {{ $stockOpname->counted_at?->format('d/m/Y H:i') }}</span></div>
        <a href="{{ route('super.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm"><i data-feather="arrow-left" class="icon-sm me-1"></i>{{ __('ui.common.back') }}</a>
    </div>

    <div class="alert alert-info">{{ __('ui.workflow.count_instructions') }}</div>

    <form id="stockOpnameCountForm">
        @csrf
        <div class="card mb-3"><div class="card-body">
            <div class="table-responsive"><table class="table table-bordered align-middle">
                <thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.workflow.system_balance_at_count') }}</th><th style="width:220px">{{ __('ui.workflow.physical_count_result') }}</th><th class="text-end">{{ __('ui.common.difference') }}</th></tr></thead>
                <tbody>
                    @foreach ($stockOpname->items as $index => $item)
                        <tr class="opname-count-row" data-system="{{ $item->system_quantity }}">
                            <td>{{ $item->product?->code ?? '—' }} — {{ $item->product?->name ?? __('ui.workflow.deleted_item') }}</td>
                            <td class="text-end">{{ number_format((float) $item->system_quantity, 4, ',', '.') }} {{ $item->product?->unit?->symbol ?: $item->product?->unit?->name }}</td>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                <input type="number" name="items[{{ $index }}][counted_quantity]" class="form-control form-control-sm text-end counted-quantity" min="0" max="999999999999.9999" step="0.0001" value="{{ $item->counted_quantity }}" required>
                            </td>
                            <td class="text-end difference-cell">{{ $item->difference === null ? '—' : number_format((float) $item->difference, 4, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </div></div>
        <div class="d-flex justify-content-end gap-2"><a href="{{ route('super.stock-opnames.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.cancel') }}</a><button type="submit" id="saveCounts" class="btn btn-primary btn-sm"><i data-feather="save" class="icon-sm me-1"></i>{{ __('ui.workflow.save_count_result') }}</button></div>
    </form>
@endsection

@push('scripts')
    <script>
        $(function() {
            const format = value => new Intl.NumberFormat('id-ID', { maximumFractionDigits: 4 }).format(value);
            $('.counted-quantity').on('input', function() {
                const row = $(this).closest('.opname-count-row');
                const value = $(this).val();
                if (value === '') {
                    row.find('.difference-cell').text('—');
                    return;
                }
                const difference = Number(value) - Number(row.data('system'));
                row.find('.difference-cell').text(`${difference > 0 ? '+' : ''}${format(difference)}`);
            });

            $('#stockOpnameCountForm').on('submit', function(event) {
                event.preventDefault();
                const button = $('#saveCounts').prop('disabled', true);
                $.ajax({
                    url: @json(route('super.stock-opnames.count.save', $stockOpname)),
                    method: 'POST',
                    data: { ...Object.fromEntries(new FormData(this)), _method: 'PUT' },
                    success: response => Swal.fire({ icon: 'success', title: '{{ __('ui.workflow.saved') }}', text: response.message }).then(() => window.location.reload()),
                    error: xhr => {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors ? Object.values(errors).flat().join('\n') : (xhr.responseJSON?.message || '{{ __('ui.workflow.count_save_failed') }}');
                        Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: message });
                        button.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
