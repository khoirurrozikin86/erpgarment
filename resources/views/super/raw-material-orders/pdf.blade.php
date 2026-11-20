<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #202938; font-size: 10px; }
        .meta { width: 100%; margin: 8px 0; }
        .meta th { text-align: left; padding-right: 12px; color: #475467; }
        .items { border-collapse: collapse; width: 100%; }
        .items td, .items th { border: 1px solid #cfd5df; padding: 4px 5px; vertical-align: top; }
        .items th { text-align: left; background: #f1f4f8; }
        .items .number { text-align: right; white-space: nowrap; }
        .items .empty { text-align: center; }
        .production-summary { margin: 0 0 10px; }
        .production-summary h2 { font-size: 11px; margin: 0 0 4px; }
    </style>
    @include('super.documents.partials.compact-pdf-styles')
</head>
<body>
    @include('super.documents.partials.company-pdf-header', ['company' => $company, 'companyLogoDataUri' => $companyLogoDataUri])
    <div class="header">
        <h1>{{ __('ui.raw_material_order.title') }}</h1>
        <div class="document-no">{{ $document->number }}</div>
    </div>

    <table class="meta">
        <tr>
            <th>{{ __('ui.raw_material_order.date') }}</th><td>{{ $document->order_date?->format('d/m/Y') ?? '—' }}</td>
            <th>{{ __('ui.raw_material_order.mo_number') }}</th><td>{{ $document->production_order_number ?: '—' }}</td>
        </tr>
        <tr>
            <th>{{ __('ui.common.recorded_by') }}</th><td colspan="3">{{ $document->creator?->name ?? '—' }}</td>
        </tr>
    </table>

    @if (!empty($productionDetails))
        <div class="production-summary">
            <h2>{{ __('variants.raw_mo_production_summary') }}</h2>
            <table class="items">
                <thead><tr>
                    <th>{{ __('ui.raw_material_order.style') }}</th>
                    <th>{{ __('ui.workflow.color') }}</th>
                    <th>{{ __('ui.workflow.size') }}</th>
                    <th class="number">{{ __('variants.raw_mo_production_quantity') }}</th>
                </tr></thead>
                <tbody>
                    @foreach ($productionDetails as $detail)
                        <tr>
                            <td>{{ $detail['style'] ?: '—' }}</td>
                            <td>{{ $detail['color'] ?: '—' }}</td>
                            <td>{{ $detail['size'] ?: '—' }}</td>
                            <td class="number">{{ \App\Support\DisplayNumber::format($detail['quantity'], 4) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <table class="items">
        <thead><tr>
            <th>{{ __('ui.common.no') }}</th>
            <th>{{ __('variants.stock_parent_code') }}</th>
            <th>{{ __('variants.stock_sku') }}</th>
            <th>{{ __('ui.raw_material_order.item_name') }}</th>
            <th>{{ __('variants.bom_type') }}</th>
            <th>{{ __('ui.workflow.color') }}</th>
            <th>{{ __('ui.workflow.size') }}</th>
            <th>{{ __('ui.raw_material_order.unit') }}</th>
            <th class="number">{{ __('ui.raw_material_order.quantity') }}</th>
        </tr></thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item['parent_code'] ?? '—' }}</td>
                    <td>{{ $item['sku_code'] ?? '—' }}</td>
                    <td>{{ $item['name'] ?? '—' }}</td>
                    <td>{{ collect($item['categories'])->map(fn ($category) => __('variants.bom_type_'.$category))->implode(', ') ?: '—' }}</td>
                    <td>{{ filled($item['color']) ? $item['color'] : '—' }}</td>
                    <td>{{ filled($item['size']) ? $item['size'] : '—' }}</td>
                    <td>{{ $item['unit'] ?? '—' }}</td>
                    <td class="number">{{ \App\Support\DisplayNumber::format($item['quantity'], 4) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">{{ __('ui.common.no_matching_records') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
