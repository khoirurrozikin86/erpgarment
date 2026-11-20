@extends('layouts.admin')

@section('title', __('ui.workflow.material_order_form_title'))
@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">{{ __('ui.nav.production') }}</li>
            <li class="breadcrumb-item"><a href="{{ route('super.material-orders.index') }}">{{ __('ui.workflow.material_order') }}</a></li>
            <li class="breadcrumb-item active">{{ __('ui.common.add') }}</li>
        </ol>
    </nav>
@endsection

@section('content')
    <form id="productionOrderDraft" class="card" method="post" enctype="multipart/form-data" action="{{ route('super.material-orders.store') }}">
        @csrf
        <div class="card-header bg-white">
            <h5 class="mb-0">{{ __('ui.workflow.material_order_form_title') }}</h5>
        </div>
        <div class="card-body">
            <div class="row gx-lg-5 gy-2">
                @foreach ([
                    ['number', __('ui.workflow.material_order_no'), 'text', 100, '', true],
                    ['buyer_po_number', __('ui.workflow.buyer_po_no'), 'text', 100, '', false],
                    ['no_cbd', __('ui.workflow.no_cbd'), 'text', 100, '', false],
                    ['currency', __('ui.workflow.currency'), 'text', 10, $currency, true],
                    ['destination', __('ui.workflow.destination'), 'text', 150, '', false],
                    ['po_date', __('ui.workflow.po_date'), 'date', null, now()->toDateString(), true],
                    ['due_date', __('ui.workflow.material_order_due_date'), 'date', null, '', true],
                ] as [$field, $label, $type, $length, $value, $required])
                    <div class="col-lg-6">
                        <div class="row g-2 align-items-center">
                            <label for="draft-{{ $field }}" class="col-5 col-form-label col-form-label-sm">{{ $label }}</label>
                            <div class="col-7">
                                <input id="draft-{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ $value }}"
                                    @if ($length) maxlength="{{ $length }}" @endif
                                    class="form-control form-control-sm" @required($required)>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="col-lg-6">
                    <div class="row g-2 align-items-center">
                        <label for="production-order-buyer" class="col-5 col-form-label col-form-label-sm">{{ __('ui.workflow.buyer') }}</label>
                        <div class="col-7">
                            @include('super.production-orders.partials.buyer-field', ['selectedBuyer' => null])
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="row g-2 align-items-start">
                        <label for="draft-image" class="col-5 col-form-label col-form-label-sm">{{ __('variants.image') }}</label>
                        <div class="col-7">
                            <input id="draft-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm">
                            <small class="text-muted d-block mt-1">JPG, PNG, WebP · 5 MB</small>
                            <img id="draft-image-preview" class="img-thumbnail d-none mt-2" alt="{{ __('variants.image') }}" style="max-width:96px;max-height:96px">
                        </div>
                    </div>
                </div>
            </div>

            <section class="border-top mt-4 pt-3">
                <h6>{{ __('ui.common.detail') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle" style="min-width:740px">
                        <thead class="table-light"><tr>
                            <th>{{ __('ui.workflow.style_code') }}</th>
                            <th>{{ __('ui.workflow.color') }}</th>
                            <th>{{ __('ui.workflow.size') }}</th>
                            <th>{{ __('ui.workflow.order_quantity') }}</th>
                            <th>{{ __('ui.workflow.price_per_unit') }}</th>
                            <th>{{ __('ui.workflow.description') }}</th>
                            <th>{{ __('ui.common.action') }}</th>
                        </tr></thead>
                        <tbody id="draft-lines"></tbody>
                    </table>
                </div>
                <button type="button" id="draft-add-line" class="btn btn-outline-primary btn-sm">{{ __('ui.common.add') }}</button>
                <p class="small text-muted mt-2 mb-0">{{ __('variants.draft_finish_good_hint') }}</p>
            </section>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('super.material-orders.index') }}" class="btn btn-light btn-sm">{{ __('ui.common.cancel') }}</a>
            <button type="submit" id="draft-save" class="btn btn-primary btn-sm">{{ __('ui.common.save') }}</button>
        </div>
    </form>

    <template id="draft-line-template">
        <tr>
            <td><input data-field="style_code" aria-label="{{ __('ui.workflow.style_code') }}" maxlength="80" class="form-control form-control-sm" required></td>
            <td>
                <select data-field="color" aria-label="{{ __('ui.workflow.color') }}" class="form-select form-select-sm" required>
                    <option value="">{{ __('ui.common.select') }}</option>
                    @foreach ($colors as $option)
                        <option value="{{ $option->code }}">{{ $option->code }}{{ $option->name !== $option->code ? ' — '.$option->name : '' }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select data-field="size" aria-label="{{ __('ui.workflow.size') }}" class="form-select form-select-sm" required>
                    <option value="">{{ __('ui.common.select') }}</option>
                    @foreach ($sizes as $option)
                        <option value="{{ $option->code }}">{{ $option->code }}{{ $option->name !== $option->code ? ' — '.$option->name : '' }}</option>
                    @endforeach
                </select>
            </td>
            <td><input data-field="planned_quantity" aria-label="{{ __('ui.workflow.order_quantity') }}" type="number" min="0.0001" step="0.0001" class="form-control form-control-sm" required></td>
            <td><input data-field="price_per_unit" aria-label="{{ __('ui.workflow.price_per_unit') }}" type="number" min="0" step="0.0001" class="form-control form-control-sm"></td>
            <td><input data-field="description" aria-label="{{ __('ui.workflow.description') }}" maxlength="5000" class="form-control form-control-sm"></td>
            <td><button type="button" class="btn btn-outline-danger btn-sm draft-remove-line" aria-label="{{ __('ui.common.delete') }}">×</button></td>
        </tr>
    </template>
    @include('super.production-orders.partials.buyer-picker')
@endsection

@push('scripts')
    <script>
        $(function () {
            const lines = $('#draft-lines');
            const add = $('#draft-add-line');
            let nextIndex = 0;

            function syncButtons() {
                const count = lines.children().length;
                add.prop('disabled', count >= 100);
                lines.find('.draft-remove-line').prop('disabled', count === 1);
            }

            function addLine(focus = false) {
                const previous = lines.children().last();
                const row = $($('#draft-line-template')[0].content.firstElementChild.cloneNode(true));
                row.find('[data-field]').each(function () {
                    this.name = `draft_lines[${nextIndex}][${this.dataset.field}]`;
                });
                for (const field of ['style_code', 'color', 'price_per_unit', 'description']) {
                    row.find('[data-field="' + field + '"]').val(previous.find('[data-field="' + field + '"]').val() ?? '');
                }
                nextIndex++;
                lines.append(row);
                syncButtons();
                if (focus) row.find('[data-field="size"]').focus();
            }

            addLine();
            add.on('click', () => addLine(true));
            lines.on('click', '.draft-remove-line', function () {
                $(this).closest('tr').remove();
                syncButtons();
            });

            let imageUrl;
            $('#draft-image').on('change', function () {
                if (imageUrl) URL.revokeObjectURL(imageUrl);
                imageUrl = this.files[0] ? URL.createObjectURL(this.files[0]) : null;
                const preview = $('#draft-image-preview').toggleClass('d-none', !imageUrl);
                if (imageUrl) preview.attr('src', imageUrl);
                else preview.removeAttr('src');
            });

            $('#productionOrderDraft').on('submit', function (event) {
                event.preventDefault();
                if (!$('#customer_id').val()) {
                    $('#production-order-buyer').trigger('click');
                    return;
                }
                const save = $('#draft-save').prop('disabled', true);
                $.ajax({
                    url: this.action,
                    method: 'POST',
                    data: new FormData(this),
                    contentType: false,
                    processData: false
                }).done(result => Swal.fire({icon: 'success', text: result.message})
                    .then(() => { window.location.href = @json(route('super.material-orders.index')); }))
                    .fail(error => {
                        save.prop('disabled', false);
                        Swal.fire({
                            icon: 'error',
                            text: Object.values(error.responseJSON?.errors || {}).flat().join('\n')
                                || error.responseJSON?.message || @json(__('ui.workflow.material_order_save_failed'))
                        });
                    });
            });
        });
    </script>
@endpush
