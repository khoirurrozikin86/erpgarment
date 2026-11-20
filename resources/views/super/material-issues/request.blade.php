@extends('layouts.admin')
@section('title', __('ui.material_issue.send'))

@section('content')
    @php
        $canSend = auth()->user()->can('material-issues.create') && $details['items']->contains(fn ($line) => $line['remaining'] > 0);
        $number = fn ($value) => \App\Support\DisplayNumber::format($value, 4);
    @endphp

    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h4 class="page-title mb-0">{{ __('ui.material_issue.send') }} — {{ $details['request_number'] }}</h4>
        <a href="{{ route('super.material-issues.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.back') }}</a>
    </div>

    <form id="productionIssueForm" method="POST" action="{{ route('super.material-issues.bulk.store') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-3">
                        <div class="text-muted small">{{ __('ui.material_issue.request_number') }}</div>
                        <div class="fw-semibold">{{ $details['order_number'] }}</div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="text-muted small">{{ __('ui.material_issue.mo_number') }}</div>
                        <div>{{ $details['mo_number'] ?: '—' }}</div>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <div class="text-muted small">{{ __('ui.material_issue.style') }}</div>
                        <div>{{ $details['style'] ?: '—' }}</div>
                    </div>
                    @if ($canSend)
                        <div class="col-sm-6 col-xl-3">
                            <label class="form-label mb-1" for="issue_date">{{ __('ui.material_issue.date') }}</label>
                            <input id="issue_date" name="issue_date" type="date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($details['skipped_codes'])
            <div class="alert alert-warning">{{ __('variants.mis_skipped_codes', ['codes' => $details['skipped_codes']]) }}</div>
        @endif

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <h6 class="mb-0">{{ __('ui.material_issue.requests_title') }}</h6>
                @if ($canSend)
                    <button type="button" id="fillIssueQuantities" class="btn btn-outline-primary btn-sm">{{ __('variants.mis_fill_quantities') }}</button>
                @endif
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>{{ __('variants.stock_parent_code') }}</th>
                        <th>{{ __('variants.stock_sku') }}</th>
                        <th>{{ __('ui.workflow.item_name') }}</th>
                        <th>{{ __('ui.workflow.color') }}</th>
                        <th>{{ __('ui.workflow.size') }}</th>
                        <th>{{ __('ui.common.unit') }}</th>
                        <th class="text-end">{{ __('ui.material_issue.request_quantity') }}</th>
                        <th class="text-end">{{ __('ui.material_issue.sent_before') }}</th>
                        <th class="text-end">{{ __('ui.material_issue.remaining_quantity') }}</th>
                        @if ($canSend)
                            <th class="text-end" style="min-width: 130px">{{ __('ui.material_issue.send_now') }}</th>
                            <th class="text-end">{{ __('ui.material_issue.total_sent') }}</th>
                        @endif
                    </tr></thead>
                    <tbody>
                        @forelse ($details['items'] as $line)
                            <tr>
                                <td>{{ $line['parent_code'] }}</td>
                                <td>{{ $line['sku_code'] }}</td>
                                <td>{{ $line['name'] }}</td>
                                <td>{{ $line['color'] }}</td>
                                <td>{{ $line['size'] }}</td>
                                <td>{{ $line['unit'] }}</td>
                                <td class="text-end">{{ $number($line['requested']) }}</td>
                                <td class="text-end">{{ $number($line['sent_before']) }}</td>
                                <td class="text-end">{{ $number($line['remaining']) }}</td>
                                @if ($canSend)
                                    <td>
                                        <input type="number" class="form-control form-control-sm text-end mis-quantity"
                                            aria-label="{{ __('ui.material_issue.send_now') }} — {{ $line['sku_code'] }}"
                                            data-sources="{{ $line['sources']->map(fn ($source) => ['id' => $source['id'], 'remaining' => $source['remaining']])->toJson() }}"
                                            data-sent="{{ $line['sent_before'] }}"
                                            min="0" max="{{ $line['remaining'] }}" step="0.0001" value="0" @disabled($line['remaining'] <= 0)>
                                    </td>
                                    <td class="text-end mis-total">{{ $number($line['sent_before']) }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">{{ __('ui.common.no_matching_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($canSend)
            <div class="text-end"><button type="submit" id="saveProductionIssue" class="btn btn-primary btn-sm">{{ __('ui.material_issue.save') }}</button></div>
        @endif
    </form>
@endsection

@push('scripts')
    @if ($canSend)
        <script>
            $(function () {
                const form = $('#productionIssueForm');
                const inputs = form.find('.mis-quantity:enabled');
                const format = new Intl.NumberFormat('id-ID', {maximumFractionDigits: 4});

                inputs.on('input', function () {
                    const total = Number(this.dataset.sent) + Number(this.value || 0);
                    $(this).closest('tr').find('.mis-total').text(format.format(total));
                });

                $('#fillIssueQuantities').on('click', function () {
                    inputs.each(function () {
                        this.value = this.max;
                    }).trigger('input');
                });

                form.on('submit', function (event) {
                    event.preventDefault();
                    const items = inputs.get().flatMap(input => {
                        let remaining = Math.round(Number(input.value || 0) * 10000);
                        if (remaining <= 0) return [];

                        return JSON.parse(input.dataset.sources).flatMap(source => {
                            const quantity = Math.min(remaining, Math.round(source.remaining * 10000));
                            remaining -= quantity;
                            return quantity > 0
                                ? [{raw_material_order_item_id: source.id, quantity: quantity / 10000}]
                                : [];
                        });
                    });
                    if (!items.length) {
                        Swal.fire({icon: 'warning', text: @json(__('ui.material_issue.quantity_invalid'))});
                        return;
                    }

                    const button = $('#saveProductionIssue').prop('disabled', true);
                    $.ajax({
                        url: this.action,
                        method: 'POST',
                        data: {_token: @json(csrf_token()), issue_date: $('#issue_date').val(), items},
                    }).done(response => {
                        Swal.fire({icon: 'success', text: `${response.message} ${response.number}`})
                            .then(() => window.location.href = @json(route('super.material-issues.index')));
                    }).fail(xhr => {
                        button.prop('disabled', false);
                        const message = Object.values(xhr.responseJSON?.errors || {}).flat().join('\n');
                        Swal.fire({icon: 'error', text: message || xhr.responseJSON?.message || @json(__('ui.material_issue.failed'))});
                    });
                });
            });
        </script>
    @endif
@endpush
