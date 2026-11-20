<!doctype html><html lang="id"><head><meta charset="utf-8"><title>{{ __('ui.gsn.print_title') }} — {{ $note->number }}</title><style>
@page{size:A4 portrait;margin:9mm}body{font:12px Arial,sans-serif;color:#111}.top{display:flex;justify-content:space-between;align-items:flex-start}.company{max-width:50%;overflow-wrap:anywhere}.company-details{width:46%;text-align:right;overflow-wrap:anywhere}.logo{font-size:23px;font-weight:bold;color:#4355bd}h2{text-align:center;font-size:17px;margin:10px 0 3px}.document-no{text-align:center;margin:0 0 8px}.meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px}.box{border:1px solid #222;padding:6px}.full{grid-column:1/-1}table{width:100%;border-collapse:collapse;font-size:10px;line-height:1.2}thead{display:table-header-group}tr{break-inside:avoid}th,td{border:1px solid #222;padding:3px 4px}th{background:#f8e967;text-align:center}.sign{display:flex;justify-content:space-between;margin-top:22px;text-align:center;break-inside:avoid}.sign div{width:30%;padding-top:12px}@media print{.no-print{display:none}}
</style></head><body>
<div class="top">
    <div class="company"><div class="logo">{{ $company->code }}</div><strong>{{ $company->name }}</strong></div>
    <div class="company-details">
        @if ($company->address)
            <div>{{ \Illuminate\Support\Str::squish($company->address) }}</div>
        @endif
        <div>{{ collect([$company->city, $company->province, $company->postal_code])->filter()->implode(', ') }}</div>
        <div>{{ collect([$company->phone, $company->email])->filter()->implode(' | ') }}</div>
    </div>
</div>
<h2>{{ __('ui.gsn.print_title') }}</h2><p class="document-no"><strong>{{ __('ui.gsn.number') }}:</strong> {{ $note->number }}</p>
<div class="meta"><div class="box"><strong>{{ __('ui.gsn.customer') }}</strong><br>{{ $note->items->first()?->customer?->name ?: $note->items->first()?->productionOrder?->buyer?->name ?: '—' }}<br>{{ $note->items->first()?->customer?->address ?: $note->items->first()?->productionOrder?->buyer?->address }}</div><div><p>{{ $company->city ?: '—' }}, {{ $note->sent_date?->translatedFormat('d F Y') }}</p><p><strong>{{ __('ui.gsn.date') }}:</strong> {{ $note->sent_date?->translatedFormat('d F Y') }}</p><p><strong>{{ __('ui.gsn.contract_number') }}:</strong> {{ $note->contract_number ?: '—' }}</p></div>
<div class="full"><strong>{{ __('ui.gsn.vehicle_number') }}:</strong> {{ $note->vehicle_number ?: '—' }} &nbsp; <strong>{{ __('ui.gsn.driver_name') }}:</strong> {{ $note->driver_name ?: '—' }} &nbsp; <strong>{{ __('ui.gsn.driver_phone') }}:</strong> {{ $note->driver_phone ?: '—' }}<br><strong>{{ __('ui.gsn.container_number') }}:</strong> {{ $note->container_number ?: '—' }} &nbsp; <strong>{{ __('ui.gsn.seal_number') }}:</strong> {{ $note->seal_number ?: '—' }}</div></div>
<table>
    <thead>
        <tr>
            <th>{{ __('ui.gsn.description_goods') }}</th>
            <th>{{ __('ui.gsn.color') }}</th>
            <th>{{ __('ui.gsn.size') }}</th>
            <th>{{ __('ui.gsn.quantity') }}</th>
            <th>{{ __('ui.gsn.remark') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($note->items as $item)
            @php
                $product = $item->product;
                $order = $item->productionOrder;
            @endphp
            <tr>
                <td style="overflow-wrap:anywhere">
                    {{ $order?->buyer_po_number }}<br>
                    {{ $product?->name }}<br>
                    {{ __('ui.gsn.mo') }}: {{ $order?->number }}<br>
                    {{ __('ui.gsn.parent_code') }}: {{ $product?->parentProduct?->code ?? $product?->code ?? '—' }}<br>
                    {{ __('ui.gsn.sku_code') }}: {{ $product?->parent_product_id ? $product->code : '—' }}
                </td>
                <td style="text-align:center">{{ ($order?->order_color ?? $product?->color) ?: '—' }}</td>
                <td style="text-align:center">{{ ($order?->order_size ?? $product?->size) ?: '—' }}</td>
                <td style="text-align:center">
                    {{ \App\Support\DisplayNumber::format((float) $item->quantity, 2) }} {{ $product?->unit?->symbol ?: $product?->unit?->name }}<br>
                    {{ number_format((float)$item->cartons,0) }} {{ __('ui.gsn.cartons') }}
                </td>
                <td>
                    {{ __('ui.gsn.route_value') }}: {{ $note->delivery_route ?: '—' }}<br>
                    {{ __('ui.gsn.vehicle_number') }}: {{ $note->vehicle_number ?: '—' }}<br>
                    {{ __('ui.gsn.seal_number') }}: {{ $note->seal_number ?: '—' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
<div class="sign"><div>{{ __('ui.gsn.received_by') }}<br><br><br><br>(__________________)</div><div>{{ __('ui.gsn.prepared_by') }}<br>{{ $company->name }}<br><br><br><br>(__________________)</div></div><button class="no-print" onclick="window.print()">{{ __('ui.common.print') }}</button></body></html>
