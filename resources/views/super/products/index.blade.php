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
                                <th width="70">{{ __('ui.workflow.image') }}</th>
                                <th>{{ __('ui.common.code') }}</th>
                                <th>{{ __('ui.workflow.barcode') }}</th>
                                <th>{{ __('ui.workflow.sku') }}</th>
                                <th>{{ __('ui.common.item_name') }}</th>
                                <th>{{ __('ui.common.category') }}</th>
                                <th>{{ __('ui.common.unit') }}</th>
                                <th class="text-end">{{ __('ui.workflow.current_stock') }}</th>
                                <th>{{ __('ui.common.type') }}</th>
                                <th>{{ __('ui.workflow.garment_type') }}</th>
                                <th>{{ __('ui.common.purchase_price') }}</th>
                                <th>{{ __('ui.common.selling_price') }}</th>
                                <th>{{ __('ui.common.status') }}</th>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>
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

        <div class="modal-dialog modal-xl modal-dialog-scrollable product-modal-dialog">

            <div class="modal-content product-modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1" id="productModalTitle">{{ __('ui.common.add_item') }}</h5>
                        <small class="text-muted">{{ __('ui.workflow.complete_item_information') }}</small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ui.common.close') }}"></button>
                </div>

                <form id="productForm" enctype="multipart/form-data">

                    @csrf

                    <input type="hidden" id="product_id" name="product_id">

                    <input type="hidden" id="form_method" value="POST">

                    <div class="modal-body product-modal-body">

                        <div id="product-form-alert"></div>

                        {{-- BASIC INFORMATION --}}
                        <h6 class="fw-bold mb-3">{{ __('ui.workflow.basic_item_information') }}</h6>

                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.common.code') }}<span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="code" name="code" maxlength="50"
                                    required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.barcode') }}</label>

                                <input type="text" class="form-control" id="barcode" name="barcode"
                                    maxlength="100">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.sku') }}</label>

                                <input type="text" class="form-control" id="sku" name="sku"
                                    maxlength="100">
                            </div>

                            <div class="col-md-8 mb-3">
                                <label class="form-label">{{ __('ui.common.item_name') }}<span class="text-danger">*</span>
                                </label>

                                <input type="text" class="form-control" id="name" name="name"
                                    maxlength="150" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.short_name') }}</label>

                                <input type="text" class="form-control" id="short_name" name="short_name"
                                    maxlength="100">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('ui.common.category') }}</label>

                                <select class="form-select" id="category_id" name="category_id">

                                    <option value="">{{ __('ui.workflow.choose_category') }}</option>

                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('ui.common.unit') }}</label>

                                <select class="form-select" id="unit_id" name="unit_id">

                                    <option value="">{{ __('ui.workflow.choose_unit') }}</option>

                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}">
                                            {{ $unit->name }}
                                            @if ($unit->symbol)
                                                ({{ $unit->symbol }})
                                            @endif
                                        </option>
                                    @endforeach

                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('ui.workflow.product_type') }}</label>

                                <select class="form-select" id="product_type" name="product_type">

                                    <option value="stock">{{ __('ui.workflow.stock') }}</option>

                                    <option value="service">{{ __('ui.workflow.service') }}</option>

                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('ui.workflow.garment_item_type') }}</label>
                                <select class="form-select" id="garment_item_type" name="garment_item_type">
                                    <option value="general">{{ __('ui.common.general') }}</option>
                                    <option value="fabric">{{ __('ui.workflow.fabric') }}</option>
                                    <option value="accessory">{{ __('ui.workflow.accessory') }}</option>
                                    <option value="work_in_process">{{ __('ui.workflow.work_in_process') }}</option>
                                    <option value="finished_good">{{ __('ui.workflow.finished_good') }}</option>
                                    <option value="packaging">{{ __('ui.workflow.packaging') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('ui.workflow.style_code') }}</label>
                                <input type="text" class="form-control" id="style_code" name="style_code" maxlength="80">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.color') }}</label>
                                <input type="text" class="form-control" id="color" name="color" maxlength="80">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.size') }}</label>
                                <input type="text" class="form-control" id="size" name="size" maxlength="40" placeholder="{{ __('ui.workflow.size_example') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.hs_code') }}</label>
                                <input type="text" class="form-control" id="hs_code" name="hs_code" maxlength="30">
                            </div>

                        </div>


                        <hr>


                        {{-- PRICE --}}
                        <h6 class="fw-bold mb-3">{{ __('ui.common.price') }}</h6>

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('ui.common.purchase_price') }}</label>

                                <input type="number" class="form-control" id="purchase_price" name="purchase_price"
                                    min="0" step="0.01" value="0">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('ui.common.selling_price') }}</label>

                                <input type="number" class="form-control" id="sales_price" name="sales_price"
                                    min="0" step="0.01" value="0">
                            </div>

                        </div>


                        <hr>


                        {{-- STOCK --}}
                        <h6 class="fw-bold mb-3">{{ __('ui.workflow.stock_settings') }}</h6>

                        <div class="row">

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.minimum_stock') }}</label>

                                <input type="number" class="form-control" id="min_stock" name="min_stock"
                                    min="0" step="0.0001" value="0">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.maximum_stock') }}</label>

                                <input type="number" class="form-control" id="max_stock" name="max_stock"
                                    min="0" step="0.0001" value="0">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('ui.workflow.reorder_point') }}</label>

                                <input type="number" class="form-control" id="reorder_point" name="reorder_point"
                                    min="0" step="0.0001" value="0">
                            </div>

                        </div>


                        <hr>


                        {{-- TAX --}}
                        <h6 class="fw-bold mb-3">{{ __('ui.common.tax') }}</h6>

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <div class="form-check form-switch mt-2">

                                    <input type="hidden" name="taxable" value="0">

                                    <input class="form-check-input" type="checkbox" id="taxable" name="taxable"
                                        value="1">

                                    <label class="form-check-label" for="taxable">{{ __('ui.workflow.taxable_item') }}</label>

                                </div>

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label">{{ __('ui.workflow.tax_rate') }}</label>

                                <input type="number" class="form-control" id="tax_rate" name="tax_rate"
                                    min="0" max="100" step="0.01" value="0">

                            </div>

                        </div>


                        <hr>


                        {{-- SETTINGS --}}
                        <h6 class="fw-bold mb-3">{{ __('ui.workflow.settings_label') }}</h6>

                        <div class="row">

                            <div class="col-md-3 mb-3">

                                <div class="form-check form-switch">

                                    <input type="hidden" name="track_stock" value="0">

                                    <input class="form-check-input" type="checkbox" id="track_stock" name="track_stock"
                                        value="1" checked>

                                    <label class="form-check-label" for="track_stock">{{ __('ui.workflow.track_stock') }}</label>

                                </div>

                            </div>

                            <div class="col-md-3 mb-3">

                                <div class="form-check form-switch">

                                    <input type="hidden" name="allow_discount" value="0">

                                    <input class="form-check-input" type="checkbox" id="allow_discount"
                                        name="allow_discount" value="1" checked>

                                    <label class="form-check-label" for="allow_discount">{{ __('ui.workflow.allow_discount') }}</label>

                                </div>

                            </div>

                            <div class="col-md-3 mb-3">

                                <div class="form-check form-switch">

                                    <input type="hidden" name="allow_purchase" value="0">

                                    <input class="form-check-input" type="checkbox" id="allow_purchase"
                                        name="allow_purchase" value="1" checked>

                                    <label class="form-check-label" for="allow_purchase">{{ __('ui.workflow.allow_purchase') }}</label>

                                </div>

                            </div>

                            <div class="col-md-3 mb-3">

                                <div class="form-check form-switch">

                                    <input type="hidden" name="allow_sales" value="0">

                                    <input class="form-check-input" type="checkbox" id="allow_sales" name="allow_sales"
                                        value="1" checked>

                                    <label class="form-check-label" for="allow_sales">{{ __('ui.workflow.allow_sales') }}</label>

                                </div>

                            </div>

                            <div class="col-md-3 mb-3">

                                <div class="form-check form-switch">

                                    <input type="hidden" name="is_active" value="0">

                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        value="1" checked>

                                    <label class="form-check-label" for="is_active">{{ __('ui.common.active') }}</label>

                                </div>

                            </div>

                        </div>


                        <hr>


                        {{-- DESCRIPTION --}}
                        <h6 class="fw-bold mb-3">{{ __('ui.common.description') }}</h6>

                        <div class="mb-3">

                            <textarea class="form-control" id="description" name="description" rows="4"
                                placeholder="{{ __('ui.workflow.item_description_placeholder') }}"></textarea>

                        </div>


                        <hr>


                        {{-- IMAGES --}}
                        <h6 class="fw-bold mb-3">{{ __('ui.workflow.product_images_heading') }}</h6>

                        <div class="mb-3">

                            <label class="form-label">{{ __('ui.workflow.upload_product_images') }}</label>

                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <label for="images" class="btn btn-outline-primary btn-sm mb-0">
                                    <i class="fas fa-images me-1"></i>{{ __('ui.workflow.choose_images') }}
                                </label>
                                <span id="selected-images-label" class="small text-muted">{{ __('ui.workflow.no_images_selected') }}</span>
                            </div>
                            <input type="file" class="visually-hidden" id="images" name="images[]"
                                accept="image/jpeg,image/png,image/webp" multiple>

                            <input type="hidden" id="primary_image_id" name="primary_image_id" value="">
                            <div id="deleted-image-inputs"></div>

                            <small class="form-text d-block mt-1">{{ __('ui.workflow.image_upload_hint') }}</small>

                        </div>

                        <div id="image-preview" class="row g-2"></div>

                        <div id="existing-images" class="row g-2"></div>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('ui.common.cancel') }}</button>

                        <button type="submit" class="btn btn-primary" id="btn-save-product">

                            <i class="fas fa-save me-1"></i>{{ __('ui.common.save') }}</button>

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

        .product-table-image {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }

        .product-modal-dialog {
            max-width: 1100px;
            height: calc(100vh - 30px);
            margin: 15px auto;
        }

        .product-modal-content {
            height: 100%;
            max-height: calc(100vh - 30px);
            display: flex;
            flex-direction: column;
        }

        .product-modal-content .modal-header,
        .product-modal-content .modal-footer {
            flex: 0 0 auto;
        }

        .product-modal-content form {
            min-height: 0;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }

        .product-modal-body {
            min-height: 0;
            flex: 1 1 auto;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 22px 24px;
        }

        .product-modal-body::-webkit-scrollbar {
            width: 7px;
        }

        .product-modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .product-modal-body::-webkit-scrollbar-thumb {
            background: #bdbdbd;
            border-radius: 10px;
        }

        .product-section {
            padding: 4px 0 22px;
            margin-bottom: 22px;
            border-bottom: 1px solid #e9ecef;
        }

        .product-section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 15px;
            margin-bottom: 16px;
        }

        .product-section-title i {
            width: 20px;
            text-align: center;
            opacity: .75;
        }

        .product-switch {
            min-height: 38px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .product-switch .form-check-input {
            flex-shrink: 0;
        }

        .product-modal-content .modal-footer {
            padding: 12px 24px;
            background: #fff;
            border-top: 1px solid #e9ecef;
        }

        .image-preview-wrapper {
            position: relative;
            display: inline-block;
        }

        .product-preview-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        @media (max-width: 991.98px) {
            .product-modal-dialog {
                max-width: 95%;
                height: calc(100vh - 20px);
                margin: 10px auto;
            }

            .product-modal-content {
                max-height: calc(100vh - 20px);
            }

            .product-modal-body {
                padding: 18px;
            }
        }

        @media (max-width: 575.98px) {
            .product-modal-dialog {
                max-width: 100%;
                width: 100%;
                height: 100vh;
                margin: 0;
            }

            .product-modal-content {
                height: 100vh;
                max-height: 100vh;
                border-radius: 0;
            }

            .product-modal-body {
                padding: 16px;
            }

            .product-modal-content .modal-footer {
                padding: 10px 16px;
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

                columns: [

                    {
                        data: 'id',
                        name: 'id',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            return meta.row +
                                meta.settings._iDisplayStart +
                                1;
                        }
                    },

                    {
                        data: 'image',
                        name: 'image',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'code',
                        name: 'code'
                    },

                    {
                        data: 'barcode',
                        name: 'barcode',
                        defaultContent: '-'
                    },

                    {
                        data: 'sku',
                        name: 'sku',
                        defaultContent: '-'
                    },

                    {
                        data: 'name',
                        name: 'name'
                    },

                    {
                        data: 'category_name',
                        name: 'category.name',
                        defaultContent: '-'
                    },

                    {
                        data: 'unit_name',
                        name: 'unit.name',
                        defaultContent: '-'
                    },

                    {
                        data: 'current_stock',
                        name: 'stock.quantity',
                        className: 'text-end',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'product_type_label',
                        name: 'product_type',
                        orderable: true,
                        searchable: true
                    },

                    {
                        data: 'garment_item_type_label',
                        name: 'garment_item_type',
                        orderable: true,
                        searchable: true
                    },

                    {
                        data: 'purchase_price_formatted',
                        name: 'purchase_price',
                        className: 'text-end'
                    },

                    {
                        data: 'sales_price_formatted',
                        name: 'sales_price',
                        className: 'text-end'
                    },

                    {
                        data: 'status',
                        name: 'is_active',
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
                $('#style_code, #color, #size, #hs_code').val('');

                $('#purchase_price').val('0');
                $('#sales_price').val('0');

                $('#min_stock').val('0');
                $('#max_stock').val('0');
                $('#reorder_point').val('0');

                $('#tax_rate').val('0');

                $('#track_stock').prop('checked', true);
                $('#allow_discount').prop('checked', true);
                $('#allow_purchase').prop('checked', true);
                $('#allow_sales').prop('checked', true);
                $('#is_active').prop('checked', true);

                $('#taxable').prop('checked', false);

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
                $('#barcode').val(payload.barcode);
                $('#sku').val(payload.sku);
                $('#name').val(payload.name);
                $('#short_name').val(payload.short_name);

                $('#category_id').val(payload.category_id);
                $('#unit_id').val(payload.unit_id);

                $('#product_type').val(payload.product_type);
                $('#garment_item_type').val(payload.garment_item_type || 'general');
                $('#style_code').val(payload.style_code || '');
                $('#color').val(payload.color || '');
                $('#size').val(payload.size || '');
                $('#hs_code').val(payload.hs_code || '');

                $('#purchase_price').val(payload.purchase_price);
                $('#sales_price').val(payload.sales_price);

                $('#min_stock').val(payload.min_stock);
                $('#max_stock').val(payload.max_stock);
                $('#reorder_point').val(payload.reorder_point);

                $('#track_stock').prop(
                    'checked',
                    Boolean(payload.track_stock)
                );

                $('#taxable').prop(
                    'checked',
                    Boolean(payload.taxable)
                );

                $('#tax_rate').val(payload.tax_rate);

                $('#allow_discount').prop(
                    'checked',
                    Boolean(payload.allow_discount)
                );

                $('#allow_purchase').prop(
                    'checked',
                    Boolean(payload.allow_purchase)
                );

                $('#allow_sales').prop(
                    'checked',
                    Boolean(payload.allow_sales)
                );

                $('#is_active').prop(
                    'checked',
                    Boolean(payload.is_active)
                );

                $('#description').val(payload.description);

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
                                '<i class="fas fa-save me-1"></i> ' + @json(__('ui.common.save'))
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
