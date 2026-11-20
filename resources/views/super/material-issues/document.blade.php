@extends('layouts.admin')
@section('title', __('ui.material_issue.title'))

@section('content')
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
        <h4 class="page-title mb-0">{{ __('ui.material_issue.title') }} — {{ $details['number'] }}</h4>
        <div class="d-flex gap-2">
            <a href="{{ route('super.material-issues.documents.pdf', $documentId) }}" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener">{{ __('ui.workflow.view_pdf') }}</a>
            <a href="{{ route('super.material-issues.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.common.back') }}</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <dl class="row g-2 mb-3">
                <div class="col-sm-4"><dt>{{ __('ui.material_issue.date') }}</dt><dd>{{ $details['issue_date'] ?: '—' }}</dd></div>
                <div class="col-sm-4"><dt>{{ __('ui.material_issue.request_number') }}</dt><dd>{{ $details['request_number'] }}</dd></div>
                <div class="col-sm-4"><dt>{{ __('ui.material_issue.mo_number') }}</dt><dd>{{ $details['mo_number'] ?: '—' }}</dd></div>
            </dl>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light"><tr>
                        <th>{{ __('variants.stock_parent_code') }}</th>
                        <th>{{ __('variants.stock_sku') }}</th>
                        <th>{{ __('ui.workflow.item_name') }}</th>
                        <th>{{ __('ui.workflow.color') }}</th>
                        <th>{{ __('ui.workflow.size') }}</th>
                        <th>{{ __('ui.common.unit') }}</th>
                        <th class="text-end">{{ __('ui.material_issue.sent_now') }}</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($details['items'] as $item)
                            <tr>
                                <td>{{ $item['parent_code'] ?? '—' }}</td>
                                <td>{{ $item['sku_code'] ?? '—' }}</td>
                                <td>{{ $item['name'] ?? '—' }}</td>
                                <td>{{ $item['color'] }}</td>
                                <td>{{ $item['size'] }}</td>
                                <td>{{ $item['unit'] ?: '—' }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format($item['quantity'], 4, ',', '.'), '0'), ',') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">{{ __('ui.common.no_matching_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
