<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $issue->number }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #202938; font-size: 11px; }
        h1 { font-size: 20px; margin: 0 0 5px; }
        .muted { color: #667085; }
        .header { border-bottom: 2px solid #263b5e; padding-bottom: 13px; margin-bottom: 16px; }
        .document-no { font-size: 13px; font-weight: bold; color: #263b5e; }
        .meta { width: 100%; margin-bottom: 18px; }
        .meta td { padding: 5px 8px 5px 0; vertical-align: top; }
        .meta .label { width: 135px; color: #667085; }
        table.items { border-collapse: collapse; width: 100%; }
        .items th, .items td { border: 1px solid #cfd5df; padding: 7px 8px; }
        .items th { background: #f1f4f8; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; padding: 8px 18px; }
        .sign-line { border-bottom: 1px solid #475467; height: 48px; margin-bottom: 6px; }
        .footer { margin-top: 20px; border-top: 1px solid #d0d5dd; padding-top: 7px; font-size: 9px; color: #667085; }
    </style>
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
            <th class="center" style="width:32px">{{ __('ui.common.no') }}</th>
            <th>{{ __('ui.material_issue.source_item_number') }}</th>
            <th>{{ __('ui.common.item_code') }}</th>
            <th>{{ __('ui.common.item_name') }}</th>
            <th>{{ __('ui.material_issue.style') }}</th>
            <th>{{ __('ui.common.unit') }}</th>
            <th class="right" style="width:100px">{{ __('ui.common.quantity') }}</th>
        </tr></thead>
        <tbody>
            @forelse ($issue->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->rawMaterialOrderItem?->number ?? '—' }}</td>
                    <td>{{ $item->product?->code ?? '—' }}</td>
                    <td>{{ $item->product?->name ?? '—' }}</td>
                    <td>{{ $item->rawMaterialOrderItem?->style_code ?? '—' }}</td>
                    <td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—' }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 4, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">{{ __('ui.common.no_matching_records') }}</td></tr>
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
