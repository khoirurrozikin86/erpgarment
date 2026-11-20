@extends('layouts.admin')

@php
    $title = $contract
        ? __('ui.common.edit').' '.__('ui.subcontract.contracts_title')
        : __('ui.subcontract.add_contract');
    $fields = [
        'contract_number' => ['type' => 'text', 'value' => $number, 'readonly' => true],
        'bc_decree_number' => ['type' => 'text', 'value' => $contract?->bc_decree_number, 'maxlength' => 150],
        'description' => ['type' => 'textarea', 'value' => $contract?->description],
        'starts_on' => ['type' => 'date', 'value' => $contract?->starts_on?->toDateString() ?? now()->toDateString(), 'required' => true],
        'ends_on' => ['type' => 'date', 'value' => $contract?->ends_on?->toDateString() ?? now()->toDateString(), 'required' => true],
        'quota' => ['type' => 'number', 'value' => $contract?->quota],
    ];
@endphp

@section('title', $title)
@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="mb-0">{{ $title }}</h5>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('super.subcontract.index') }}">{{ __('ui.common.back') }}</a>
    </div>
    <form method="POST" action="{{ $contract ? route('super.subcontract.contracts.update', $contract) : route('super.subcontract.contracts.store') }}">
        @csrf
        @if($contract)
            @method('PUT')
        @endif
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger" role="alert"><ul class="mb-0">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul></div>
            @endif
            <div class="row g-3 g-xl-4">
                @foreach([['subcontractor_id', 'contract_number', 'bc_decree_number', 'description'], ['starts_on', 'ends_on', 'quota']] as $column)
                    <div class="col-lg-6">
                        @foreach($column as $key)
                            @php($field = $fields[$key] ?? null)
                            <div class="row g-2 align-items-center mb-2">
                                <label class="col-md-4 col-form-label col-form-label-sm" for="{{ $key }}">
                                    {{ match ($key) {
                                        'subcontractor_id' => __('ui.subcontract.subcontractor'),
                                        'description' => __('ui.common.description'),
                                        default => __('ui.subcontract.'.$key),
                                    } }}
                                </label>
                                <div class="col-md-8">
                                    @if($key === 'subcontractor_id')
                                        <input type="hidden" id="contract-customer-id" name="customer_id" value="{{ $customer?->is_active ? $customer->id : '' }}">
                                        <div class="input-group input-group-sm">
                                            <input id="{{ $key }}" class="form-control" value="{{ $customer?->name ?? $contract?->subcontractor?->name }}" readonly>
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#contract-customer-modal">
                                                <i data-feather="search" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.search') }}
                                            </button>
                                        </div>
                                    @elseif($field['type'] === 'textarea')
                                        <textarea id="{{ $key }}" name="{{ $key }}" class="form-control form-control-sm" rows="2" maxlength="5000">{{ old($key, $field['value']) }}</textarea>
                                    @else
                                        <input id="{{ $key }}" type="{{ $field['type'] }}" class="form-control form-control-sm"
                                            @if(empty($field['readonly'])) name="{{ $key }}" @endif
                                            value="{{ old($key, $field['value']) }}"
                                            @readonly($field['readonly'] ?? false) @required($field['required'] ?? false)
                                            @if(isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif
                                            @if($key === 'quota') min="0" step="0.0001" @endif>
                                        @if($key === 'contract_number' && !$contract)
                                            <div class="form-text">{{ __('ui.subcontract.number_help') }}</div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm"><i data-feather="save" class="icon-sm me-1" aria-hidden="true"></i>{{ __('ui.common.save') }}</button>
        </div>
    </form>
</div>
<div class="modal fade" id="contract-customer-modal" tabindex="-1" aria-labelledby="contract-customer-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contract-customer-title">{{ __('ui.common.select') }} {{ __('ui.common.customer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered align-middle" id="contract-customer-table" style="width: 100%">
                    <thead><tr><th>{{ __('ui.common.code') }}</th><th>{{ __('ui.common.name') }}</th><th>{{ __('ui.common.actions') }}</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = document.getElementById('contract-customer-modal');
    const table = $('#contract-customer-table');
    let customers;

    modal.addEventListener('shown.bs.modal', () => {
        if (customers) {
            customers.columns.adjust();
            return;
        }

        customers = table.DataTable({
            processing: true,
            serverSide: true,
            ajax: @json(route('super.subcontract.contracts.customers.dt')),
            columns: [
                {data: 'code', name: 'code', render: $.fn.dataTable.render.text()},
                {data: 'name', name: 'name', render: $.fn.dataTable.render.text()},
                {data: null, orderable: false, searchable: false, defaultContent: '',
                    createdCell(cell) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-sm btn-primary js-select-contract-customer';
                        button.textContent = @json(__('ui.common.select'));
                        cell.replaceChildren(button);
                    }
                },
            ],
            order: [[1, 'asc']],
        });
    });

    table.on('click', '.js-select-contract-customer', function () {
        const customer = customers.row(this.closest('tr')).data();
        document.getElementById('contract-customer-id').value = customer.id;
        document.getElementById('subcontractor_id').value = customer.name;
        bootstrap.Modal.getInstance(modal).hide();
    });
});
</script>
@endpush
