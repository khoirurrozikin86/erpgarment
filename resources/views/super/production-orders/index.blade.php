@extends('layouts.admin')

@section('title', __('ui.workflow.production_order'))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item">{{ __('ui.nav.inventory') }}</li>
        <li class="breadcrumb-item active">{{ __('ui.workflow.production_order') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="page-title mb-1">{{ __('ui.workflow.production_order') }}</h4>
            <p class="text-muted mb-0">{{ __('ui.workflow.production_order_subtitle') }}</p>
        </div>
        @can('production-orders.create')
            <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#createProductionOrder">
                <i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.workflow.create_production_order') }}
            </button>
        @endcan
    </div>

    @can('production-orders.create')
        <div class="collapse mb-3" id="createProductionOrder">
            <div class="card"><div class="card-body">
                <h6 class="fw-bold mb-3">{{ __('ui.workflow.production_order_form_title') }}</h6>
                <form id="productionOrderForm">
                    @csrf
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="output_product_id" class="form-label">{{ __('ui.workflow.bom_product') }} <span class="text-danger">*</span></label>
                            <select name="output_product_id" id="output_product_id" class="form-select" required>
                                <option value="">{{ __('ui.workflow.bom_choose_product') }}</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->code }} — {{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="planned_quantity" class="form-label">{{ __('ui.workflow.production_quantity') }} <span class="text-danger">*</span></label>
                            <input type="text" name="planned_quantity" id="planned_quantity" class="form-control" inputmode="decimal" autocomplete="off" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="planned_start_date" class="form-label">{{ __('ui.workflow.planned_start_date') }}</label>
                            <input type="date" name="planned_start_date" id="planned_start_date" class="form-control">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="planned_end_date" class="form-label">{{ __('ui.workflow.planned_end_date') }}</label>
                            <input type="date" name="planned_end_date" id="planned_end_date" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label for="production_notes" class="form-label">{{ __('ui.common.notes') }}</label>
                            <textarea name="notes" id="production_notes" class="form-control" rows="2" maxlength="5000"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" id="saveProductionOrder">{{ __('ui.workflow.save_production_order') }}</button>
                </form>
            </div></div>
        </div>
    @endcan

    <div class="card"><div class="card-body"><div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead><tr>
                <th>{{ __('ui.common.number') }}</th><th>{{ __('ui.workflow.bom_product') }}</th>
                <th>{{ __('ui.workflow.production_quantity') }}</th><th>{{ __('ui.workflow.bom_used') }}</th>
                <th>{{ __('ui.workflow.planned_start_date') }}</th><th>{{ __('ui.common.status') }}</th><th>{{ __('ui.common.action') }}</th>
            </tr></thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td class="fw-semibold">{{ $order->number }}</td>
                        <td>{{ $order->outputProduct?->code }} — {{ $order->outputProduct?->name }}</td>
                        <td class="text-end">{{ number_format((float) $order->planned_quantity, 4, ',', '.') }} {{ $order->outputProduct?->unit?->symbol }}</td>
                        <td>v{{ $order->bom_version }} ({{ $order->materials_count }} {{ __('ui.workflow.material_lines') }})</td>
                        <td>{{ $order->planned_start_date?->format('d/m/Y') ?? '—' }}</td>
                        <td><span class="badge {{ $order->status === 'released' ? 'bg-primary' : ($order->status === 'in_progress' ? 'bg-info text-dark' : ($order->status === 'draft' ? 'bg-warning text-dark' : 'bg-secondary')) }}">{{ __('ui.workflow.production_status_' . $order->status) }}</span></td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-secondary view-production-order" data-url="{{ route('super.production-orders.show', $order) }}">{{ __('ui.workflow.view_detail') }}</button>
                            @can('production-orders.release')
                                @if ($order->status === 'draft')
                                    <button type="button" class="btn btn-sm btn-success transition-production-order" data-status="released" data-url="{{ route('super.production-orders.transition', $order) }}">{{ __('ui.workflow.release_production_order') }}</button>
                                @elseif ($order->status === 'released')
                                    <button type="button" class="btn btn-sm btn-primary transition-production-order" data-status="in_progress" data-url="{{ route('super.production-orders.transition', $order) }}">{{ __('ui.workflow.start_production') }}</button>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('ui.workflow.no_production_orders') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div></div>

    <div class="modal fade" id="productionOrderDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="productionOrderDetailTitle"></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body" id="productionOrderDetailBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        const parseQuantity = value => {
            let normalized = String(value ?? '').trim().replace(/\s/g, '');
            if (!normalized) return NaN;
            const comma = normalized.lastIndexOf(',');
            const dot = normalized.lastIndexOf('.');
            if (comma >= 0 && dot >= 0) {
                normalized = comma > dot
                    ? normalized.replace(/\./g, '').replace(',', '.')
                    : normalized.replace(/,/g, '');
            } else if (comma >= 0) {
                normalized = normalized.replace(',', '.');
            }
            return Number(normalized);
        };

        @can('production-orders.create')
            $('#productionOrderForm').on('submit', function (event) {
                event.preventDefault();
                const button = $('#saveProductionOrder').prop('disabled', true);
                const data = $(this).serializeArray();
                const quantity = data.find(field => field.name === 'planned_quantity');
                if (quantity) {
                    const parsedQuantity = parseQuantity(quantity.value);
                    if (Number.isFinite(parsedQuantity)) quantity.value = String(parsedQuantity);
                }
                $.ajax({url: @json(route('super.production-orders.store')), method: 'POST', data})
                    .done(response => { Swal.fire({icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message}).then(() => window.location.reload()); })
                    .fail(xhr => Swal.fire({icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || xhr.responseJSON?.message || '{{ __('ui.workflow.production_order_save_failed') }}'}))
                    .always(() => button.prop('disabled', false));
            });
        @endcan

        $(document).on('click', '.view-production-order', function () {
            $.get($(this).data('url')).done(order => {
                const rows = order.materials.map(item => `<tr><td>${$('<div>').text(item.code).html()} — ${$('<div>').text(item.product).html()}</td><td>${$('<div>').text(item.operation).html()}</td><td class="text-end">${item.required_quantity} ${$('<div>').text(item.unit).html()}</td></tr>`).join('');
                @can('production-orders.update')
                    const stageAction = stage => stage.is_recordable ? `<button class="btn btn-sm btn-outline-primary record-production-stage" data-stage="${stage.stage}" data-label="${$('<div>').text(stage.label).html()}" data-available="${stage.available_quantity}">{{ __('ui.workflow.record_stage') }}</button>` : '';
                @else
                    const stageAction = () => '';
                @endcan
                const stages = order.stages.map(stage => `<tr><td>${$('<div>').text(stage.label).html()}</td><td class="text-end">${stage.input_quantity}</td><td class="text-end">${stage.good_quantity}</td><td class="text-end">${stage.rejected_quantity}</td><td class="text-end">${stage.waste_quantity}</td><td class="text-end">${Number(stage.available_quantity).toLocaleString()}</td><td>${stageAction(stage)}</td></tr>`).join('');
                const entries = order.stage_entries.length ? order.stage_entries.map(entry => `<tr><td>${$('<div>').text(entry.stage_label).html()}</td><td class="text-end">${entry.input_quantity}</td><td class="text-end">${entry.good_quantity}</td><td class="text-end">${entry.rejected_quantity}</td><td class="text-end">${entry.waste_quantity}</td><td>${entry.processed_at}</td></tr>`).join('') : `<tr><td colspan="6" class="text-center text-muted">{{ __('ui.workflow.no_stage_records') }}</td></tr>`;
                $('#productionOrderDetailTitle').text(`{{ __('ui.workflow.production_order') }} ${order.number}`);
                $('#productionOrderDetailBody').data('stage-url', order.stage_url);
                const statuses = {draft: '{{ __('ui.workflow.production_status_draft') }}', released: '{{ __('ui.workflow.production_status_released') }}', in_progress: '{{ __('ui.workflow.production_status_in_progress') }}', completed: '{{ __('ui.workflow.production_status_completed') }}', cancelled: '{{ __('ui.workflow.production_status_cancelled') }}'};
                $('#productionOrderDetailBody').html(`<dl class="row"><dt class="col-sm-4">{{ __('ui.workflow.bom_product') }}</dt><dd class="col-sm-8">${$('<div>').text(order.product_code + ' — ' + order.product).html()}</dd><dt class="col-sm-4">{{ __('ui.workflow.production_quantity') }}</dt><dd class="col-sm-8">${order.planned_quantity} ${$('<div>').text(order.unit).html()}</dd><dt class="col-sm-4">{{ __('ui.workflow.bom_used') }}</dt><dd class="col-sm-8">v${$('<div>').text(order.bom_version).html()}</dd><dt class="col-sm-4">{{ __('ui.common.status') }}</dt><dd class="col-sm-8">${statuses[order.status] || '—'}</dd><dt class="col-sm-4">{{ __('ui.common.notes') }}</dt><dd class="col-sm-8">${$('<div>').text(order.notes).html()}</dd></dl><h6>{{ __('ui.workflow.production_material_snapshot') }}</h6><div class="table-responsive mb-3"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th>{{ __('ui.workflow.bom_operation') }}</th><th class="text-end">{{ __('ui.workflow.required_quantity') }}</th></tr></thead><tbody>${rows}</tbody></table></div><h6>{{ __('ui.workflow.wip_stage_progress') }}</h6><div class="table-responsive mb-3"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.workflow.production_stage') }}</th><th class="text-end">{{ __('ui.workflow.stage_input') }}</th><th class="text-end">{{ __('ui.workflow.stage_good') }}</th><th class="text-end">{{ __('ui.workflow.stage_rejected') }}</th><th class="text-end">{{ __('ui.workflow.stage_waste') }}</th><th class="text-end">{{ __('ui.workflow.stage_available') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead><tbody>${stages}</tbody></table></div><h6>{{ __('ui.workflow.stage_history') }}</h6><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.workflow.production_stage') }}</th><th>{{ __('ui.workflow.stage_input') }}</th><th>{{ __('ui.workflow.stage_good') }}</th><th>{{ __('ui.workflow.stage_rejected') }}</th><th>{{ __('ui.workflow.stage_waste') }}</th><th>{{ __('ui.common.date') }}</th></tr></thead><tbody>${entries}</tbody></table></div>`);
                bootstrap.Modal.getOrCreateInstance(document.getElementById('productionOrderDetailModal')).show();
            }).fail(() => Swal.fire({icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.production_order_detail_failed') }}'}));
        });

        @can('production-orders.update')
            $(document).on('click', '.record-production-stage', function () {
                const button = $(this);
                const available = Number(button.data('available'));
                Swal.fire({
                    target: document.getElementById('productionOrderDetailModal'),
                    title: `{{ __('ui.workflow.record_stage') }} — ${button.data('label')}`,
                    html: `<p class="text-muted">{{ __('ui.workflow.stage_available') }}: ${available}</p><div class="mb-2 text-start"><label class="form-label">{{ __('ui.workflow.stage_input') }}</label><input id="stageInputQty" class="form-control" type="text" inputmode="decimal" autocomplete="off" value="${available}"></div><div class="mb-2 text-start"><label class="form-label">{{ __('ui.workflow.stage_good') }}</label><input id="stageGoodQty" class="form-control" type="text" inputmode="decimal" autocomplete="off" value="${available}"></div><div class="mb-2 text-start"><label class="form-label">{{ __('ui.workflow.stage_rejected') }}</label><input id="stageRejectedQty" class="form-control" type="text" inputmode="decimal" autocomplete="off" value="0"></div><div class="mb-2 text-start"><label class="form-label">{{ __('ui.workflow.stage_waste') }}</label><input id="stageWasteQty" class="form-control" type="text" inputmode="decimal" autocomplete="off" value="0"></div><div class="text-start"><label class="form-label">{{ __('ui.common.notes') }}</label><input id="stageNotes" class="form-control" maxlength="2000"></div>`,
                    showCancelButton: true,
                    focusConfirm: false,
                    didOpen: () => document.getElementById('stageInputQty').focus(),
                    confirmButtonText: '{{ __('ui.workflow.save_stage_record') }}',
                    cancelButtonText: '{{ __('ui.common.cancel') }}',
                    preConfirm: () => {
                        const input = parseQuantity($('#stageInputQty').val());
                        const good = parseQuantity($('#stageGoodQty').val());
                        const rejected = parseQuantity($('#stageRejectedQty').val());
                        const waste = parseQuantity($('#stageWasteQty').val());
                        if (!Number.isFinite(input) || input <= 0 || input > available) { Swal.showValidationMessage('{{ __('ui.workflow.stage_input_invalid') }}'); return false; }
                        if (![good, rejected, waste].every(quantity => Number.isFinite(quantity) && quantity >= 0)) { Swal.showValidationMessage('{{ __('ui.workflow.stage_output_must_match_input') }}'); return false; }
                        if (Math.abs(input - good - rejected - waste) > 0.0001) { Swal.showValidationMessage('{{ __('ui.workflow.stage_output_must_match_input') }}'); return false; }
                        return {stage: button.data('stage'), input_quantity: input, good_quantity: good, rejected_quantity: rejected, waste_quantity: waste, notes: $('#stageNotes').val()};
                    }
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.ajax({url: $('#productionOrderDetailBody').data('stage-url'), method: 'POST', data: {_token: @json(csrf_token()), ...result.value}})
                        .done(response => Swal.fire({icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message}).then(() => window.location.reload()))
                        .fail(xhr => Swal.fire({icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || xhr.responseJSON?.message || '{{ __('ui.workflow.production_stage_record_failed') }}'}));
                });
            });
        @endcan

        @can('production-orders.release')
            $(document).on('click', '.transition-production-order', function () {
                const button = $(this);
                Swal.fire({icon: 'question', title: '{{ __('ui.workflow.confirm_production_transition') }}', showCancelButton: true, confirmButtonText: '{{ __('ui.common.confirm') }}', cancelButtonText: '{{ __('ui.common.cancel') }}'})
                    .then(result => {
                        if (!result.isConfirmed) return;
                        $.ajax({url: button.data('url'), method: 'PUT', data: {_token: @json(csrf_token()), status: button.data('status')}})
                            .done(response => Swal.fire({icon: 'success', title: '{{ __('ui.workflow.success') }}', text: response.message}).then(() => window.location.reload()))
                            .fail(xhr => Swal.fire({icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: Object.values(xhr.responseJSON?.errors || {}).flat().join('\n') || xhr.responseJSON?.message || '{{ __('ui.workflow.production_order_status_failed') }}'}));
                    });
            });
        @endcan
    });
</script>
@endpush
