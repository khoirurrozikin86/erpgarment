@extends('layouts.admin')

@section('title', __( 'ui.common.receipt' ))

@section('breadcrumb')
    <nav class="page-breadcrumb"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="#">{{ __('ui.common.purchasing') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('ui.common.goods_receipt') }}</li>
    </ol></nav>
@endsection

@section('content')
    <div class="row">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h4 class="page-title mb-1">{{ __('ui.common.goods_receipt') }}</h4><span class="text-muted">{{ __('ui.workflow.receipt_list_subtitle') }}</span></div>
            @can('goods-receipts.create')
                <a href="{{ route('super.goods-receipts.create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>{{ __('ui.common.record_receipt') }}</a>
            @endcan
        </div>
        <div class="col-12">
            @include('super.purchasing.date-filter')
            <div class="card"><div class="card-body"><div class="table-responsive">
            <table id="goods-receipts-table" class="table table-bordered table-hover align-middle w-100">
                <thead><tr><th>{{ __('ui.common.no') }}</th><th>{{ __('ui.common.number') }}</th><th>{{ __('ui.common.po_number') }}</th><th>{{ __('ui.common.supplier') }}</th><th>{{ __('ui.common.received_date') }}</th><th>{{ __('ui.common.row_count') }}</th><th>{{ __('ui.common.received_by') }}</th><th>{{ __('ui.common.action') }}</th></tr></thead>
            </table>
            </div></div></div>
        </div>
    </div>

    <div class="modal fade" id="viewGoodsReceiptModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="viewGoodsReceiptTitle">{{ __('ui.workflow.receipt_detail') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button></div>
            <div class="modal-body" id="viewGoodsReceiptBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('ui.common.close') }}</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const modal = new bootstrap.Modal(document.getElementById('viewGoodsReceiptModal'));
            const money = value => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 2 }).format(Number(value || 0));
            const safe = value => $('<div>').text(value ?? '').html();
            const table = $('#goods-receipts-table').DataTable({
                processing: true, serverSide: true, responsive: true,
                ajax: {
                    url: @json(route('super.goods-receipts.dt')),
                    data: data => { data.from_date = $('#from_date').val(); data.to_date = $('#to_date').val(); }
                },
                columns: [
                    { data: 'id', orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + meta.settings._iDisplayStart + 1 },
                    { data: 'number', name: 'number' },
                    { data: 'purchase_order_number', orderable: false, searchable: false },
                    { data: 'supplier_name', orderable: false, searchable: false },
                    { data: 'received_at', name: 'received_at', render: data => data ? new Date(data).toLocaleString('id-ID') : '—' },
                    { data: 'items_count', searchable: false },
                    { data: 'receiver_name', orderable: false, searchable: false },
                    { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
                ],
                order: [[4, 'desc']],
                drawCallback: function() { if (window.feather) feather.replace(); }
            });

            $('#applyDateFilter').on('click', () => table.ajax.reload());
            $('#todayDateFilter').on('click', () => {
                const today = @json(now()->toDateString());
                $('#from_date, #to_date').val(today);
                table.ajax.reload();
            });

            $(document).on('click', '.btn-view-goods-receipt', function() {
                $.get($(this).data('url')).done(function(receipt) {
                    const rows = receipt.items.map(item => `<tr><td>${safe(item.code)} — ${safe(item.name)}</td><td class="text-end">${item.quantity} ${safe(item.unit)}</td><td class="text-end">${money(item.unit_price)}</td><td class="text-end">${money(item.line_total)}</td></tr>`).join('');
                    $('#viewGoodsReceiptTitle').text(`Penerimaan ${receipt.number}`);
                    $('#viewGoodsReceiptBody').html(`
                        <dl class="row mb-3"><dt class="col-sm-3">{{ __('ui.common.purchase_order') }}</dt><dd class="col-sm-9">${safe(receipt.purchase_order)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.supplier') }}</dt><dd class="col-sm-9">${safe(receipt.supplier)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.received_date') }}</dt><dd class="col-sm-9">${safe(receipt.received_at)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.received_by') }}</dt><dd class="col-sm-9">${safe(receipt.receiver)}</dd>
                        <dt class="col-sm-3">{{ __('ui.common.notes') }}</dt><dd class="col-sm-9 text-break">${safe(receipt.notes)}</dd></dl>
                        <div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>{{ __('ui.common.item') }}</th><th class="text-end">{{ __('ui.common.received') }}</th><th class="text-end">{{ __('ui.common.unit_price') }}</th><th class="text-end">{{ __('ui.common.subtotal') }}</th></tr></thead><tbody>${rows}</tbody></table></div>`);
                    modal.show();
                }).fail(() => Swal.fire({ icon: 'error', title: '{{ __('ui.workflow.failure') }}', text: '{{ __('ui.workflow.receipt_detail_load_failed') }}' }));
            });
        });
    </script>
@endpush
