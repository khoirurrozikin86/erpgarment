@extends('layouts.admin')

@section('title', __('ui.common.item'))

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item">
                <a href="#">{{ __('ui.common.master') }}</a>
            </li>

            <li class="breadcrumb-item active" aria-current="page">
                Products
            </li>

        </ol>
    </nav>
@endsection

@section('content')
    <div class="row">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">{{ __('ui.common.item') }}</h4>
                <p class="text-muted mb-0">
                    {{ __('ui.master.items_subtitle') }}
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @can('products.view')
                    <a href="{{ route('super.products.export') }}" class="btn btn-outline-success">
                        <i class="fas fa-file-export me-1"></i>{{ __('ui.common.export_excel') }}</a>
                    <a href="{{ route('super.products.template') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-download me-1"></i> {{ __('ui.master.template') }}
                    </a>
                @endcan
                @can('products.create')
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                        data-bs-target="#productImportModal">
                        <i class="fas fa-file-import me-1"></i> {{ __('ui.workflow.import_items') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="btn-add-product">
                        <i class="fas fa-plus me-1"></i>{{ __('ui.common.add_item') }}</button>
                @endcan
            </div>
        </div>

        @if (session('import_success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __('ui.workflow.success') }}',
                        text: @json(session('import_success')),
                        confirmButtonText: '{{ __('ui.master.ok') }}'
                    });
                });
            </script>
        @endif
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table id="products-table" class="table table-bordered table-hover align-middle w-100">

                        <thead>
                            <tr>
                                <th width="50">{{ __('ui.common.no') }}</th>
                                <th>{{ __('ui.common.category') }}</th>
                                <th>{{ __('ui.common.code') }}</th>
                                <th>{{ __('ui.common.item_name') }}</th>
                                <th>{{ __('ui.common.unit') }}</th>
                                <th>{{ __('ui.workflow.style_code') }}</th>
                                <th>{{ __('ui.workflow.color') }}</th>
                                <th>{{ __('ui.workflow.size') }}</th>
                                <th>Available</th>
                                <th width="70">{{ __('ui.common.actions') }}</th>
                            </tr>
                        </thead>

                        <tbody></tbody>

                    </table>
                </div>

            </div>
        </div>

    </div>

    {{-- MODAL IMPORT PRODUCT --}}
    <div class="modal fade" id="productImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('super.products.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title">{{ __('ui.workflow.import_items') }}</h5>
                            <small class="text-muted">{{ __('ui.workflow.select_filled_template') }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="{{ __('ui.common.close') }}"></button>
                    </div>
                    <div class="modal-body">
                        @error('file')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        <label for="product-import-file" class="form-label">{{ __('ui.common.file_excel_csv') }}</label>
                        <input type="file" id="product-import-file" name="file" class="form-control"
                            accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">{{ __('ui.workflow.supported_file_format') }}</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-import me-1"></i>{{ __('ui.workflow.import_items') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- ========================================================= --}}
    {{-- MODAL PRODUCT --}}
    {{-- ========================================================= --}}

    <div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">

            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="productModalTitle">{{ __('ui.common.add_item') }}</h5>
                        <small class="text-muted">{{ __('ui.workflow.complete_item_information') }}</small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="{{ __('ui.common.close') }}"></button>
                </div>

                <form id="productForm">
                    @csrf
                    <input type="hidden" id="product_id" name="product_id">
                    <input type="hidden" id="form_method" value="POST">

                    <div class="modal-body">
                        <div id="product-form-alert"></div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="category_id">{{ __('ui.common.category') }}</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">{{ __('ui.workflow.choose_category') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="code">{{ __('ui.common.code') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" maxlength="50"
                                    required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="name">{{ __('ui.common.item_name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    maxlength="150" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="unit_id">{{ __('ui.common.unit') }}</label>
                                <select class="form-select" id="unit_id" name="unit_id">
                                    <option value="">{{ __('ui.workflow.choose_unit') }}</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->name }}@if ($unit->symbol)
                                                ({{ $unit->symbol }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3 position-relative">
                                <label class="form-label" for="style-search">{{ __('ui.workflow.style_code') }}</label>
                                <input type="search" class="form-control" id="style-search" autocomplete="off"
                                    placeholder="Cari kode atau nama style">
                                <input type="hidden" id="style_code" name="style_code">
                                <div id="style-search-results"
                                    class="list-group position-absolute start-0 end-0 shadow-sm"
                                    style="z-index:1080;max-height:220px;overflow-y:auto;display:none"></div>
                                <div class="form-text">Pilih style dari master Style.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="color">{{ __('ui.workflow.color') }}</label>
                                <input type="text" class="form-control" id="color" name="color"
                                    maxlength="80">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="size">{{ __('ui.workflow.size') }}</label>
                                <input type="text" class="form-control" id="size" name="size"
                                    maxlength="40">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="is_available">Available</label>
                                <select class="form-select" id="is_available" name="is_available" required>
                                    <option value="1">Masih Ada</option>
                                    <option value="0">Tidak Ada</option>
                                </select>
                            </div>
                        </div>

                        {{-- Field operasional stok tetap tersimpan untuk alur inventory. --}}
                        <input type="hidden" id="product_type" name="product_type" value="stock">
                        <input type="hidden" id="garment_item_type" name="garment_item_type" value="general">
                        <input type="hidden" id="purchase_price" name="purchase_price" value="0">
                        <input type="hidden" id="min_stock" name="min_stock" value="0">
                        <input type="hidden" id="max_stock" name="max_stock" value="0">
                        <input type="hidden" id="reorder_point" name="reorder_point" value="0">
                        <input type="hidden" id="track_stock" name="track_stock" value="1">
                        <input type="hidden" id="is_active" name="is_active" value="1">
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="btn-save-product"><i
                                class="fas fa-save me-1"></i>{{ __('ui.common.save') }}</button>
                    </div>
                </form>

            </div>

        </div>
    </div>

@endsection


@push('styles')
    <style>
        #products-table td {
            vertical-align: middle;
        }

        #productModal .modal-body {
            padding: 1rem 1.25rem;
        }

        @media (max-width: 575.98px) {
            #productModal .modal-dialog {
                margin: .5rem;
            }

            #productModal .modal-body {
                padding: 1rem;
            }
        }
    </style>
@endpush


@push('scripts')
    <script>
        function safeFeatherReplace() {
            if (!window.feather || !feather.icons) return;

            document.querySelectorAll('[data-feather]').forEach(el => {
                const name = el.getAttribute('data-feather') || 'settings';

                if (!feather.icons[name]) {
                    el.setAttribute('data-feather', 'settings');
                }
            });

            try {
                feather.replace();
            } catch (e) {}
        }



        $(function() {

            @if ($errors->has('file'))
                new bootstrap.Modal(document.getElementById('productImportModal')).show();
            @endif

            let editing = false;
            const imageLabels = {
                primary: @json(__('ui.workflow.primary_image')),
                setPrimary: @json(__('ui.workflow.set_primary_image')),
                delete: @json(__('ui.workflow.delete_image')),
                alt: @json(__('ui.workflow.product_image_alt')),
                noImages: @json(__('ui.workflow.no_images_selected')),
                selectedCount: @json(__('ui.workflow.selected_images_count')),
                editItem: @json(__('ui.workflow.edit_item_title')),
            };
            const createImageTransfer = () => {
                try {
                    return typeof DataTransfer === 'undefined' ? null : new DataTransfer();
                } catch (error) {
                    return null;
                }
            };
            let selectedImageFiles = createImageTransfer();

            const productModalElement =
                document.getElementById('productModal');

            const productModal =
                new bootstrap.Modal(productModalElement);

            const styleOptionsUrl = @json(route('super.products.style-options'));
            let styleSearchTimer = null;

            function setStylePicker(code) {
                $('#style_code').val(code || '');
                $('#style-search').val(code || '');
                $('#style-search-results').hide().empty();
                if (!code) return;
                $.getJSON(styleOptionsUrl, {
                    q: code
                }).done(function(response) {
                    const style = (response.data || []).find(item => item.code === code);
                    if (style) $('#style-search').val(`${style.code} — ${style.name}`);
                });
            }

            $('#style-search').on('keydown', function(event) {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                $('#style-search-results .style-search-option').first().trigger('click');
            });

            $('#style-search').on('input', function() {
                const term = $(this).val().trim();
                $('#style_code').val('');
                clearTimeout(styleSearchTimer);
                if (term.length < 1) {
                    $('#style-search-results').hide().empty();
                    return;
                }
                styleSearchTimer = setTimeout(function() {
                    $.getJSON(styleOptionsUrl, {
                        q: term
                    }).done(function(response) {
                        const results = $('#style-search-results').empty();
                        (response.data || []).forEach(function(style) {
                            $('<button>', {
                                    type: 'button',
                                    class: 'list-group-item list-group-item-action style-search-option'
                                })
                                .text(`${style.code} — ${style.name}`)
                                .data('style', style)
                                .appendTo(results);
                        });
                        if (!results.children().length) {
                            $('<div>', {
                                class: 'list-group-item text-muted'
                            }).text('Style tidak ditemukan.').appendTo(results);
                        }
                        results.show();
                    });
                }, 200);
            });

            $('#style-search-results').on('click', '.style-search-option', function() {
                const style = $(this).data('style');
                $('#style_code').val(style.code);
                $('#style-search').val(`${style.code} — ${style.name}`);
                $('#style-search-results').hide().empty();
            });
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#style-search, #style-search-results').length) {
                    $('#style-search-results').hide();
                }
            });


            // =========================================================
            // DATATABLE
            // =========================================================

            const table = $('#products-table').DataTable({

                processing: true,
                serverSide: true,
                responsive: true,

                ajax: {
                    url: "{{ route('super.products.dt') }}",
                    type: "GET"
                },

                columns: [{
                        data: 'id',
                        name: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    {
                        data: 'category_name',
                        name: 'category.name',
                        defaultContent: '-'
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'unit_name',
                        name: 'unit.name',
                        defaultContent: '-'
                    },
                    {
                        data: 'style_code',
                        name: 'style_code',
                        defaultContent: '-'
                    },
                    {
                        data: 'color',
                        name: 'color',
                        defaultContent: '-'
                    },
                    {
                        data: 'size',
                        name: 'size',
                        defaultContent: '-'
                    },
                    {
                        data: 'availability',
                        name: 'is_available',
                        orderable: true,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],

                order: [
                    [2, 'desc']
                ]

            }).on('draw.dt', safeFeatherReplace);


            // =========================================================
            // RESET FORM
            // =========================================================

            function getDeletedImageIds() {
                return $('#deleted-image-inputs input[name="deleted_image_ids[]"]')
                    .map(function() {
                        return Number($(this).val());
                    })
                    .get()
                    .filter(id => !isNaN(id) && id > 0);
            }

            function setDeletedImageIds(ids) {
                const container = $('#deleted-image-inputs');
                container.empty();

                (Array.isArray(ids) ? ids : []).forEach(function(id) {
                    if (id === null || id === undefined || id === '') {
                        return;
                    }

                    const input = $('<input>', {
                        type: 'hidden',
                        name: 'deleted_image_ids[]',
                        value: id
                    });

                    container.append(input);
                });
            }

            function renderExistingImages(images = []) {

                $('#existing-images').empty();

                if (!Array.isArray(images) || !images.length) {
                    $('#primary_image_id').val('');
                    setDeletedImageIds([]);
                    return;
                }

                const selectedPrimaryId = images.find(img => img.is_primary)?.id ?? images[0].id ?? '';
                $('#primary_image_id').val(selectedPrimaryId);

                images.forEach(function(image) {
                    const src = image.path || image.url || '';

                    if (!src) {
                        return;
                    }

                    const isPrimary = Number(image.id) === Number(selectedPrimaryId);

                    const html = `
                        <div class="col-md-2">
                            <div class="image-preview-wrapper position-relative">
                                <img src="${src}"
                                     class="product-preview-image"
                                     alt="${image.file_name || imageLabels.alt}">
                                <span class="badge ${isPrimary ? 'bg-primary' : 'bg-secondary'} position-absolute top-0 start-0 m-2 primary-badge" style="display: ${isPrimary ? 'inline-block' : 'none'};">
                                    ${imageLabels.primary}
                                </span>
                                <button type="button"
                                    class="btn btn-sm ${isPrimary ? 'btn-primary' : 'btn-outline-primary'} set-primary-image-btn"
                                    data-image-id="${image.id}"
                                    style="position:absolute; bottom:8px; right:8px; padding:4px 8px; font-size:11px;">
                                    ${isPrimary ? imageLabels.primary : imageLabels.setPrimary}
                                </button>
                                <button type="button"
                                    class="btn btn-danger delete-image-btn"
                                    data-image-id="${image.id}"
                                    title="${imageLabels.delete}"
                                    aria-label="${imageLabels.delete}"
                                    style="position:absolute; top:8px; right:8px; width:28px; height:28px; padding:0; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:14px; line-height:1; box-shadow:0 2px 6px rgba(0,0,0,.18); z-index:2;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    `;

                    $('#existing-images').append(html);
                });

            }

            function resetProductForm() {

                $('#productForm')[0].reset();

                $('#product_id').val('');
                $('#form_method').val('POST');

                $('#product_type').val('stock');
                $('#garment_item_type').val('general');
                $('#style_code, #color, #size').val('');


                $('#purchase_price').val('0');

                $('#min_stock').val('0');
                $('#max_stock').val('0');
                $('#reorder_point').val('0');

                $('#track_stock').val('1');
                $('#is_active').val('1');
                $('#is_available').val('1');
                $('#style-search').val('');
                $('#style-search-results').hide().empty();

                $('#images').val('');
                selectedImageFiles = createImageTransfer();
                $('#selected-images-label').text(imageLabels.noImages);
                $('#image-preview').empty();
                $('#existing-images').empty();
                $('#deleted-image-inputs').empty();
                $('#primary_image_id').val('');

                $('#product-form-alert').empty();

                editing = false;

            }


            // =========================================================
            // ADD
            // =========================================================

            $('#btn-add-product').on('click', function() {

                resetProductForm();

                $('#productModalTitle').text('{{ __('ui.common.add_item') }}');

                productModal.show();

            });


            // =========================================================
            // IMAGE PREVIEW
            // =========================================================

            $(document).on('click', '.set-primary-image-btn', function(e) {
                e.preventDefault();

                const imageId = $(this).data('image-id');
                $('#primary_image_id').val(imageId);

                $('.set-primary-image-btn').each(function() {
                    const isSelected = Number($(this).data('image-id')) === Number(imageId);
                    $(this).text(isSelected ? imageLabels.primary : imageLabels.setPrimary);
                    $(this).toggleClass('btn-primary', isSelected);
                    $(this).toggleClass('btn-outline-primary', !isSelected);
                    $(this).closest('.image-preview-wrapper').find('.primary-badge').toggle(
                        isSelected);
                });
            });

            $(document).on('click', '.delete-image-btn', function(e) {
                e.preventDefault();

                const imageId = Number($(this).data('image-id'));
                const deleted = getDeletedImageIds();

                if (!deleted.includes(imageId)) {
                    deleted.push(imageId);
                    setDeletedImageIds(deleted);
                }

                $(this).closest('.col-md-2').remove();

                const remainingImages = $('.set-primary-image-btn').map(function() {
                    return Number($(this).data('image-id'));
                }).get();

                if (remainingImages.length) {
                    const selectedPrimary = Number($('#primary_image_id').val());
                    const fallbackId = remainingImages.includes(selectedPrimary) ?
                        selectedPrimary :
                        remainingImages[0];
                    $('#primary_image_id').val(fallbackId);

                    $('.set-primary-image-btn').each(function() {
                        const isSelected = Number($(this).data('image-id')) === fallbackId;
                        $(this).text(isSelected ? imageLabels.primary : imageLabels.setPrimary);
                        $(this).toggleClass('btn-primary', isSelected);
                        $(this).toggleClass('btn-outline-primary', !isSelected);
                        $(this).closest('.image-preview-wrapper').find('.primary-badge').toggle(
                            isSelected);
                    });
                } else {
                    $('#primary_image_id').val('');
                }
            });

            $('#images').on('change', function() {

                const incomingFiles = Array.from(this.files || []);
                if (!incomingFiles.length) {
                    return;
                }

                if (selectedImageFiles) {
                    const existingFiles = new Set(Array.from(selectedImageFiles.files).map(file =>
                        `${file.name}:${file.size}:${file.lastModified}`));
                    incomingFiles.forEach(file => {
                        const signature = `${file.name}:${file.size}:${file.lastModified}`;
                        if (!existingFiles.has(signature)) {
                            selectedImageFiles.items.add(file);
                            existingFiles.add(signature);
                        }
                    });
                    this.files = selectedImageFiles.files;
                }

                const files = selectedImageFiles ? Array.from(selectedImageFiles.files) : incomingFiles;
                $('#image-preview').empty();
                $('#selected-images-label').text(imageLabels.selectedCount.replace(':count', files.length));

                Array.from(files).forEach(function(file) {

                    if (!file.type.startsWith('image/')) {
                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = function(e) {

                        const html = `
                    <div class="col-md-2">
                        <div class="image-preview-wrapper">
                                <img src="${e.target.result}"
                                     class="product-preview-image"
                                     alt="${imageLabels.alt}">
                        </div>
                    </div>
                `;

                        $('#image-preview').append(html);

                    };

                    reader.readAsDataURL(file);

                });

            });


            // =========================================================
            // EDIT
            // =========================================================

            $(document).on('click', '.btn-edit-role', function() {

                const button = $(this);

                let payload = button.attr('data-payload') || '{}';

                try {
                    payload = JSON.parse(payload);
                } catch (error) {
                    console.error('Invalid product payload:', error);
                    return;
                }

                resetProductForm();

                editing = true;

                $('#productModalTitle').text(imageLabels.editItem);

                $('#product_id').val(payload.id);

                $('#code').val(payload.code);
                $('#name').val(payload.name);

                $('#category_id').val(payload.category_id);
                $('#unit_id').val(payload.unit_id);

                $('#product_type').val(payload.product_type);
                $('#garment_item_type').val(payload.garment_item_type || 'general');
                $('#style_code').val(payload.style_code || '');
                $('#color').val(payload.color || '');
                $('#size').val(payload.size || '');

                $('#purchase_price').val(payload.purchase_price);

                $('#min_stock').val(payload.min_stock);
                $('#max_stock').val(payload.max_stock);
                $('#reorder_point').val(payload.reorder_point);

                $('#track_stock').val(payload.track_stock ? '1' : '0');

                $('#is_active').val(payload.is_active ? '1' : '0');
                $('#is_available').val(payload.is_available ? '1' : '0');
                setStylePicker(payload.style_code || '');

                renderExistingImages(payload.images || []);

                productModal.show();

            });


            // =========================================================
            // SUBMIT
            // =========================================================

            $('#productForm').on('submit', function(e) {

                e.preventDefault();

                const form = this;

                const formData = new FormData(form);

                formData.delete('deleted_image_ids[]');

                getDeletedImageIds().forEach(function(id) {
                    formData.append('deleted_image_ids[]', id);
                });

                let url = "{{ route('super.products.store') }}";

                if (editing) {

                    const productId =
                        $('#product_id').val();

                    url =
                        "{{ url('super/products') }}/" +
                        productId;

                    formData.append('_method', 'PUT');
                }

                $('#btn-save-product')
                    .prop('disabled', true)
                    .html(
                        '<i class="fas fa-spinner fa-spin me-1"></i> ' + @json(__('ui.workflow.saving'))
                    );


                $.ajax({

                    url: url,

                    type: 'POST',

                    data: formData,

                    processData: false,
                    contentType: false,

                    headers: {
                        'Accept': 'application/json'
                    },

                    success: function(response) {

                        productModal.hide();

                        table.ajax.reload(null, false);

                        Swal.fire({
                            icon: 'success',
                            title: '{{ __('ui.workflow.success') }}',
                            text: response.message,
                            timer: 1800,
                            showConfirmButton: false
                        });

                    },

                    error: function(xhr) {

                        let message =
                            '{{ __('ui.master.save_failed') }}';

                        if (xhr.status === 422) {

                            const errors =
                                xhr.responseJSON?.errors;

                            if (errors) {

                                const messages = [];

                                Object.values(errors)
                                    .forEach(function(items) {

                                        items.forEach(function(item) {
                                            messages.push(item);
                                        });

                                    });

                                message = messages.join('<br>');
                            }

                        } else if (xhr.responseJSON?.message) {

                            message =
                                xhr.responseJSON.message;

                        }

                        Swal.fire({
                            icon: 'error',
                            title: '{{ __('ui.workflow.failure') }}',
                            html: message
                        });

                    },

                    complete: function() {

                        $('#btn-save-product')
                            .prop('disabled', false)
                            .html(
                                '<i class="fas fa-save me-1"></i> ' +
                                @json(__('ui.common.save'))
                            );

                    }

                });

            });


            // =========================================================
            // DELETE
            // =========================================================

            $(document).on('click', '.btn-delete-role', function() {

                const button = $(this);

                let payload = button.attr('data-payload') || '{}';

                try {
                    payload = JSON.parse(payload);
                } catch (error) {
                    console.error('Invalid product payload:', error);
                    return;
                }

                const url =
                    button.data('url');

                const escapedName = $('<div>').text(payload.name ?? '').html();


                Swal.fire({

                    title: '{{ __('ui.workflow.delete_item_title') }}',

                    html: @json(__('ui.workflow.delete_item_body', ['name' => '__ITEM_NAME__'])).replace('__ITEM_NAME__', escapedName),

                    icon: 'warning',

                    showCancelButton: true,

                    confirmButtonText: '{{ __('ui.workflow.delete_product_confirm') }}',

                    cancelButtonText: '{{ __('ui.common.cancel') }}',

                    reverseButtons: true

                }).then(function(result) {

                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({

                        url: url,

                        type: 'POST',

                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            _method: 'DELETE'
                        },

                        headers: {
                            'Accept': 'application/json'
                        },

                        success: function(response) {

                            table.ajax.reload(null, false);

                            Swal.fire({
                                icon: 'success',
                                title: '{{ __('ui.workflow.success') }}',
                                text: response.message,
                                timer: 1800,
                                showConfirmButton: false
                            });

                        },

                        error: function(xhr) {

                            Swal.fire({
                                icon: 'error',
                                title: '{{ __('ui.workflow.failure') }}',
                                text: xhr.responseJSON?.message ??
                                    '{{ __('ui.workflow.item_delete_failed') }}'
                            });

                        }

                    });

                });

            });


        });
    </script>
@endpush
