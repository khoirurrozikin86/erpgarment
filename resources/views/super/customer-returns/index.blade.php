@extends('layouts.admin')

@section('title', 'Customer Return')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">{{ __('ui.common.sales') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.customer_return') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="page-title mb-1">{{ __('ui.common.customer_return') }}</h4><span class="text-muted">Catat barang yang dikembalikan
                    customer dan refund melalui kas/bank.</span>
            </div>
            @can('customer-returns.create')
                <a href="{{ route('super.customer-returns.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus"
                        class="icon-sm me-1"></i>{{ __('ui.common.record_return') }}</a>
            @endcan
        </div>
        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="customer-returns-table" class="table table-bordered table-hover align-middle w-100">
                            <thead>
                                <tr>
                                    <th>{{ __('ui.common.no') }}</th>
                                    <th>{{ __('ui.common.return_number') }}</th>
                                    <th>{{ __('ui.common.invoice') }}</th>
                                    <th>{{ __('ui.common.customer') }}</th>
                                    <th>{{ __('ui.common.returned_date') }}</th>
                                    <th>{{ __('ui.common.refund') }}</th>
                                    <th>{{ __('ui.common.cash_bank_account') }}</th>
                                    <th>{{ __('ui.common.recorded_by') }}</th>
                                    <th>{{ __('ui.common.action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewCustomerReturnModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewCustomerReturnTitle">Detail Customer Return</h5><button type="button"
                        class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                </div>
                <div class="modal-body" id="viewCustomerReturnBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm"
                        data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewCustomerReturnModal'));
            const money = value => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 2
            }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#customer-returns-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: @json(route('super.customer-returns.dt')),
                    data: data => {
                        data.from_date = $('#from_date').val();
                        data.to_date = $('#to_date').val();
                    }
                },
                columns: [{
                        data: 'id',
                        orderable: false,
                        searchable: false,
                        render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1
                    },
                    {
                        data: 'number',
                        name: 'number'
                    },
                    {
                        data: 'invoice_number',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'customer_name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'returned_at',
                        name: 'returned_at',
                        render: data => data ? new Date(data).toLocaleString('id-ID') : '—'
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        searchable: false,
                        className: 'text-end',
                        render: data => money(data)
                    },
                    {
                        data: 'cash_account',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'returner_name',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [4, 'desc']
                ],
                drawCallback: function() {
                    if (window.feather) feather.replace();
                }
            });

            $('#applyDateFilter').on('click', () => table.ajax.reload());
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
            });

            $(document).on('click', '.btn-view-customer-return', function() {
                $.get($(this).data('url')).done(function(item) {
                    const rows = item.items.map(row =>
                        `<tr><td>${safe(row.code)} — ${safe(row.name)}</td><td class="text-end">${row.quantity} ${safe(row.unit)}</td><td class="text-end">${money(row.unit_price)}</td><td class="text-end">${money(row.line_total)}</td></tr>`
                        ).join('');
                    $('#viewCustomerReturnTitle').text(`Customer Return ${item.number}`);
                    $('#viewCustomerReturnBody').html(
                        `
                        <dl class="row mb-3"><dt class="col-sm-3">{{ __('ui.common.source_invoice') }}</dt><dd class="col-sm-9">${safe(item.invoice)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.customer') }}</dt><dd class="col-sm-9">${safe(item.customer)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.refund_account') }}</dt><dd class="col-sm-9">${safe(item.cash_account)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.returned_date') }}</dt><dd class="col-sm-9">${safe(item.returned_at)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.recorded_by') }}</dt><dd class="col-sm-9">${safe(item.returner)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.reason') }}</dt><dd class="col-sm-9 text-break">${safe(item.reason)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.notes') }}</dt><dd class="col-sm-9 text-break">${safe(item.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.quantity') }}</th><th class="text-end">{{ __('ui.common.unit_price') }}</th><th class="text-end">{{ __('ui.common.return_value') }}</th></tr></thead><tbody>${rows}</tbody><tfoot>
                        <tr><th colspan="3" class="text-end">{{ __('ui.common.subtotal') }}</th><th class="text-end">${money(item.subtotal)}</th></tr>
                        <tr><th colspan="3" class="text-end">{{ __('ui.common.tax') }}</th><th class="text-end">${money(item.tax_amount)}</th></tr>
                        <tr><th colspan="3" class="text-end">{{ __('ui.common.refund') }}</th><th class="text-end">${money(item.total_amount)}</th></tr></tfoot></table></div>`
                        );
                    modal.show();
                }).fail(() => Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Detail customer return tidak dapat dimuat.'
                }));
            });
        });
    </script>
@endpush
