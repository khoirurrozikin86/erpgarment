@extends('layouts.admin')

@section('title', __('variants.master_style'))
@section('content')
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <h4 class="page-title mb-0">{{ __('variants.master_style') }}</h4>
        <a href="{{ route('super.boms.index') }}" class="btn btn-outline-primary btn-sm">BOM</a>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table id="bom-styles-table" class="table table-sm table-hover align-middle w-100">
                <thead class="table-light"><tr>
                    <th>{{ __('ui.workflow.style_code') }}</th>
                    <th>{{ __('ui.workflow.detail_finished_good') }}</th>
                    <th>{{ __('ui.common.status') }}</th>
                    <th>{{ __('ui.common.actions') }}</th>
                </tr></thead>
                <tbody>
                    @foreach ($styles as $style)
                        <tr>
                            <td class="fw-semibold text-nowrap">{{ $style['code'] }}</td>
                            <td>{{ $style['products'] ?: '—' }}</td>
                            <td>
                                @if ($style['has_active_bom'])
                                    <span class="badge bg-success">{{ __('variants.style_has_bom') }}</span>
                                @elseif ($style['bom_count'])
                                    <span class="badge bg-secondary">{{ __('variants.style_inactive_bom') }}</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ __('variants.style_no_bom') }}</span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($style['bom_count'])
                                    <a href="{{ route('super.boms.index', ['style_code' => $style['code']]) }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.detail') }}</a>
                                @endif
                                @can('boms.create')
                                    <a href="{{ route('super.boms.create', ['style_code' => $style['code']]) }}" class="btn btn-outline-primary btn-sm">{{ __('variants.style_add_composition') }}</a>
                                @endcan
                                @can('styles.delete')
                                    @if ($style['id'])
                                        <form method="POST" action="{{ route('super.boms.styles.destroy', $style['id']) }}" class="d-inline style-delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('ui.common.delete') }}</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('#bom-styles-table').on('submit', '.style-delete-form', function (event) {
                event.preventDefault();
                const form = this;
                Swal.fire({
                    icon: 'warning',
                    title: @json(__('ui.common.confirm_delete')),
                    showCancelButton: true,
                    confirmButtonText: @json(__('ui.common.delete')),
                    cancelButtonText: @json(__('ui.common.cancel')),
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });

            @if (session('success'))
                Swal.fire({icon: 'success', text: @json(session('success'))});
            @elseif ($errors->has('style'))
                Swal.fire({icon: 'error', text: @json($errors->first('style'))});
            @endif

            $('#bom-styles-table').DataTable({
                columnDefs: [{targets: 3, searchable: false, orderable: false}],
                language: {
                    search: @json(__('ui.common.search')),
                    emptyTable: @json(__('ui.workflow.empty_table')),
                    zeroRecords: @json(__('ui.workflow.no_matching_data')),
                    lengthMenu: @json(__('ui.workflow.show_entries')),
                    info: @json(__('ui.workflow.showing_entries')),
                    infoEmpty: @json(__('ui.workflow.no_data')),
                    paginate: {previous: @json(__('ui.workflow.previous')), next: @json(__('ui.workflow.next'))}
                }
            });
        });
    </script>
@endpush
