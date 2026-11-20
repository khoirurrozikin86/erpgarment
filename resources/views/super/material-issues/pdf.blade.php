<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $issue->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #202938; font-size: 11px; }
        .muted { color: #667085; }
        .meta { width: 100%; margin-bottom: 12px; table-layout: fixed; font-size: 9px; line-height: 1.2; }
        .meta td { padding: 3px 5px 3px 0; vertical-align: top; }
        .meta .label { width: 135px; color: #667085; }
        table.items { border-collapse: collapse; table-layout: fixed; width: 100%; font-size: 8px; line-height: 1.2; }
        .items th, .items td { border: 1px solid #cfd5df; padding: 3px 3px; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        .items th { background: #f1f4f8; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; padding: 8px 18px; }
        .sign-line { border-bottom: 1px solid #475467; height: 48px; margin-bottom: 6px; }
        .footer { margin-top: 20px; border-top: 1px solid #d0d5dd; padding-top: 7px; font-size: 9px; color: #667085; }
    </style>
    @include('super.documents.partials.compact-pdf-styles')
</head>
<body>
    @include('super.documents.partials.company-pdf-header', ['company' => $company, 'companyLogoDataUri' => $companyLogoDataUri])
    @php
        $firstItem = $issue->items->first();
        $moNumber = $firstItem?->rawMaterialOrderItem?->sourceMaterial?->productionOrder?->number
            ?: $issue->rawMaterialOrder?->production_order_number ?: '—';
    @endphp
    <div class="header">
        <h1>{{ __('ui.material_issue.pdf_title') }}</h1>
        <div class="document-no">{{ $issue->number }}</div>
    </div>

    <table class="meta">
        <tr><td class="label">{{ __('ui.material_issue.date') }}</td><td>{{ $issue->issue_date?->format('d/m/Y') ?? '—' }}</td><td class="label">{{ __('ui.material_issue.mo_number') }}</td><td>{{ $moNumber }}</td></tr>
        <tr><td class="label">{{ __('ui.material_issue.request_number') }}</td><td>{{ $issue->rawMaterialOrder?->number ?? $firstItem?->rawMaterialOrderItem?->number ?? '—' }}</td><td class="label">{{ __('ui.common.recorded_by') }}</td><td>{{ $issue->creator?->name ?? '—' }}</td></tr>
    </table>

    <table class="items">
        <thead><tr>
            <th class="center" style="width:4%">{{ __('ui.common.no') }}</th>
            <th style="width:12%">{{ __('ui.material_issue.source_item_number') }}</th>
            <th style="width:12%">{{ __('variants.stock_parent_code') }}</th>
            <th style="width:15%">{{ __('variants.stock_sku') }}</th>
            <th style="width:19%">{{ __('ui.common.item_name') }}</th>
            <th style="width:9%">{{ __('ui.material_issue.style') }}</th>
            <th style="width:7%">{{ __('ui.workflow.color') }}</th>
            <th style="width:6%">{{ __('ui.workflow.size') }}</th>
            <th style="width:5%">{{ __('ui.common.unit') }}</th>
            <th class="right" style="width:11%">{{ __('ui.common.quantity') }}</th>
        </tr></thead>
        <tbody>
            @forelse ($issue->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $issue->rawMaterialOrder?->number ?? '—' }}</td>
                    <td>{{ $item->product?->parentProduct?->code ?? $item->product?->code ?? '—' }}</td>
                    <td>{{ $item->product?->code ?? '—' }}</td>
                    <td>{{ $item->product?->name ?? '—' }}</td>
                    <td>{{ $item->rawMaterialOrderItem?->style_code ?? '—' }}</td>
                    <td>{{ $item->issued_color ?? '—' }}</td>
                    <td>{{ $item->issued_size ?? '—' }}</td>
                    <td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—' }}</td>
                    <td class="right">{{ \App\Support\DisplayNumber::format((float) $item->quantity, 4) }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="center">{{ __('ui.common.no_matching_records') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures"><tr>
        <td>{{ __('ui.material_issue.warehouse') }}<div class="sign-line"></div></td>
        <td>{{ __('ui.material_issue.production') }}<div class="sign-line"></div></td>
    </tr></table>
    <div class="footer">{{ __('ui.material_issue.pdf_printed', ['number' => $issue->number, 'date' => now()->format('d/m/Y H:i')]) }}</div>
</body>
</html>
