<div class="modal fade" id="productionOrderBuyerPicker" tabindex="-1" aria-labelledby="productionOrderBuyerPickerTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productionOrderBuyerPickerTitle">{{ __('ui.workflow.buyer') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
            </div>
            <div class="modal-body table-responsive">
                <table id="productionOrderBuyerTable" class="table table-sm table-hover align-middle w-100">
                    <thead><tr>
                        <th>{{ __('ui.common.code') }}</th>
                        <th>{{ __('ui.common.name') }}</th>
                        <th>{{ __('ui.common.action') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <td>{{ $customer->code }}</td>
                                <td>{{ $customer->name }}</td>
                                <td><button type="button" class="btn btn-primary btn-sm select-production-order-buyer"
                                    data-id="{{ $customer->id }}" data-label="{{ $customer->code }} — {{ $customer->name }}">{{ __('ui.common.select') }}</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(function () {
            const picker = document.getElementById('productionOrderBuyerPicker');
            const buyer = $('#production-order-buyer');

            buyer.on('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    this.click();
                }
            });
            picker.addEventListener('shown.bs.modal', () => {
                const table = $('#productionOrderBuyerTable').DataTable({
                    retrieve: true,
                    lengthChange: false,
                    pageLength: 10,
                    columnDefs: [{targets: 2, orderable: false, searchable: false}],
                    language: {
                        search: @json(__('ui.common.search')),
                        emptyTable: @json(__('ui.workflow.empty_table')),
                        zeroRecords: @json(__('ui.workflow.no_matching_data')),
                        info: @json(__('ui.workflow.showing_entries')),
                        infoEmpty: @json(__('ui.workflow.no_data')),
                        paginate: {
                            previous: @json(__('ui.workflow.previous')),
                            next: @json(__('ui.workflow.next'))
                        }
                    }
                });
                table.columns.adjust();
                $(picker).find('.dataTables_filter input, .dt-search input').first().focus();
            });
            $(picker).on('click', '.select-production-order-buyer', function () {
                $('#customer_id').val(this.dataset.id);
                buyer.val(this.dataset.label);
                bootstrap.Modal.getInstance(picker).hide();
            });
        });
    </script>
@endpush
