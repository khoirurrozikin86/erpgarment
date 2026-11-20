@extends('layouts.admin')
@section('title', $pageTitle)
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ $formTitle }}</h5>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route($reportRoute.'index') }}">
            {{ __('bc_reports.back') }}
        </a>
    </div>
    <form method="GET" action="{{ route($reportRoute.'trace.results', $product) }}">
        @if (!empty($selectedReceiptItem))
            <input type="hidden" name="receipt_item" value="{{ $selectedReceiptItem->id }}">
        @endif
        <div class="card-body">
            @include('super.reports.partials.trace-item-fields', [
                'traceColor' => $traceColor,
                'traceSize' => $traceSize,
                'horizontal' => true,
            ])

            <div class="border-top mt-3 pt-3">
                <div class="row">
                    <div class="col-xl-6">
                        <div class="row g-2 align-items-center">
                            <label class="col-md-4 col-form-label col-form-label-sm" for="trace-mo-picker">
                                {{ __('bc_reports.select_mo') }}
                            </label>
                            <div class="col-md-8">
                                <input type="hidden" id="trace-mo-id" name="mo" value="{{ $selected?->id }}">
                                <button type="button" id="trace-mo-picker"
                                    class="btn btn-sm btn-outline-secondary w-100 d-flex justify-content-between align-items-center gap-2 text-start"
                                    data-bs-toggle="modal" data-bs-target="#trace-mo-modal"
                                    aria-haspopup="dialog" aria-controls="trace-mo-modal">
                                    <span id="trace-mo-number">{{ $selected?->number ?? __('bc_reports.all') }}</span>
                                    <i data-feather="search" class="icon-sm" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm">{{ __('bc_reports.show_data') }}</button>
        </div>
    </form>
</div>
<div class="modal fade" id="trace-mo-modal" tabindex="-1" aria-labelledby="trace-mo-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="trace-mo-title">{{ __('bc_reports.select_mo') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('bc_reports.close') }}"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered align-middle w-100" id="trace-mo-table">
                    <thead>
                        <tr>
                            <th>{{ __('bc_reports.mo_no') }}</th>
                            <th>{{ __('ui.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>{{ $order->number }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary" data-mo-id="{{ $order->id }}"
                                        data-mo-number="{{ $order->number }}" data-bs-dismiss="modal">
                                        {{ __('ui.common.select') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-mo-id=""
                    data-mo-number="{{ __('bc_reports.all') }}" data-bs-dismiss="modal">
                    {{ __('bc_reports.all') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const traceMoModal = document.getElementById('trace-mo-modal');
    const traceMoTable = $('#trace-mo-table');

    traceMoModal.addEventListener('shown.bs.modal', () => {
        const table = traceMoTable.DataTable({
            retrieve: true,
            columnDefs: [{ targets: 1, orderable: false, searchable: false }],
        });
        table.columns.adjust();
    });

    traceMoModal.addEventListener('click', event => {
        const choice = event.target.closest('button[data-mo-id]');
        if (!choice) return;

        document.getElementById('trace-mo-id').value = choice.dataset.moId;
        document.getElementById('trace-mo-number').textContent = choice.dataset.moNumber;
    });
</script>
@endpush
