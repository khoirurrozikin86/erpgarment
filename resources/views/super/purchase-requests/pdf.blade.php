<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $purchaseRequest->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #202938; font-size: 11px; }
        .muted { color: #667085; }
        .meta { width: 100%; margin-bottom: 18px; }
        .meta td { padding: 4px 8px 4px 0; vertical-align: top; }
        .meta .label { width: 145px; color: #667085; }
        .section-title { font-size: 12px; font-weight: bold; margin: 16px 0 8px; }
        table.items { border-collapse: collapse; width: 100%; }
        .items th, .items td { border: 1px solid #cfd5df; padding: 6px 5px; }
        .items th { background: #f1f4f8; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .note { border: 1px solid #cfd5df; min-height: 40px; padding: 8px; white-space: pre-line; }
        .total { font-weight: bold; background: #f1f4f8; }
        .signatures { width: 100%; margin-top: 38px; }
        .signatures td { width: 50%; text-align: center; padding: 8px 18px; }
        .sign-line { border-bottom: 1px solid #475467; height: 48px; margin-bottom: 6px; }
        .footer { margin-top: 20px; border-top: 1px solid #d0d5dd; padding-top: 7px; font-size: 9px; color: #667085; }
    </style>
    @include('super.documents.partials.compact-pdf-styles')
</head>
<body>
    @include('super.documents.partials.company-pdf-header', ['company' => $company, 'companyLogoDataUri' => $companyLogoDataUri])
    <div class="header">
        <h1>{{ __('ui.common.purchase_request') }}</h1>
        <div class="muted">Permintaan persetujuan pembelian barang</div>
        <div class="document-no">{{ $purchaseRequest->number }}</div>
    </div>

    <table class="meta">
        <tr><td class="label">Referensi Material Request</td><td>{{ $purchaseRequest->materialRequest?->number ?? '—' }}</td><td class="label">{{ __('ui.common.request_date') }}</td><td>{{ $purchaseRequest->created_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">{{ __('ui.common.supplier') }}</td><td>{{ $purchaseRequest->supplier?->name ?? 'Supplier dihapus' }}</td><td class="label">{{ __('ui.common.requester') }}</td><td>{{ $purchaseRequest->requester?->name ?? 'User dihapus' }}</td></tr>
        <tr><td class="label">{{ __('ui.common.status') }}</td><td>{{ ['pending' => 'Menunggu Approval', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$purchaseRequest->status] ?? $purchaseRequest->status }}</td><td class="label">{{ __('ui.common.reviewed_by') }}</td><td>{{ $purchaseRequest->reviewer?->name ?? '—' }}</td></tr>
        <tr><td class="label">{{ __('ui.common.review_time') }}</td><td>{{ $purchaseRequest->reviewed_at?->format('d/m/Y H:i') ?? '—' }}</td><td class="label">Catatan Review</td><td>{{ $purchaseRequest->review_note ?: '—' }}</td></tr>
    </table>

    @if ($productionDetails->isNotEmpty())
        <div class="section-title">{{ __('ui.nav.styles') }} / {{ __('ui.workflow.order_quantity') }}</div>
        <table class="items">
            <thead><tr><th>{{ __('ui.nav.styles') }}</th><th>{{ __('ui.workflow.color') }}</th><th>{{ __('ui.workflow.size') }}</th><th class="right">{{ __('ui.workflow.order_quantity') }}</th></tr></thead>
            <tbody>
                @foreach ($productionDetails as $detail)
                    <tr><td>{{ $detail['style'] ?: '—' }}</td><td>{{ $detail['color'] ?: '—' }}</td><td>{{ $detail['size'] ?: '—' }}</td><td class="right">{{ $detail['quantity'] === null ? '—' : \App\Support\DisplayNumber::format((float) $detail['quantity'], 4) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="section-title">Rincian Pembelian</div>
    <table class="items">
        <thead><tr><th class="center" style="width:24px">{{ __('ui.common.no') }}</th><th style="width:70px">{{ __('ui.common.code') }}</th><th>{{ __('ui.common.item_name') }}</th><th style="width:48px">{{ __('ui.workflow.color') }}</th><th style="width:36px">{{ __('ui.workflow.size') }}</th><th class="right" style="width:58px">{{ __('ui.common.quantity') }}</th><th style="width:40px">{{ __('ui.common.unit') }}</th><th class="right" style="width:75px">{{ __('ui.common.unit_price') }}</th><th class="right" style="width:80px">{{ __('ui.common.subtotal') }}</th></tr></thead>
        <tbody>
            @forelse ($purchaseRequest->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->code ?? '—' }}</td>
                    <td>{{ $item->product?->name ?? 'Barang dihapus' }}</td>
                    <td>{{ $item->materialRequestItem?->color ?: $item->product?->color ?: '—' }}</td>
                    <td>{{ $item->materialRequestItem?->size ?: $item->product?->size ?: '—' }}</td>
                    <td class="right">{{ \App\Support\DisplayNumber::format((float) $item->quantity, 4) }}</td>
                    <td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—' }}</td>
                    <td class="right">Rp {{ \App\Support\DisplayNumber::format((float) $item->unit_price, 2) }}</td>
                    <td class="right">Rp {{ \App\Support\DisplayNumber::format((float) $item->line_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="center">{{ __('ui.common.no_items') }}</td></tr>
            @endforelse
            <tr class="total"><td colspan="8" class="right">{{ __('ui.common.estimated_total') }}</td><td class="right">Rp {{ \App\Support\DisplayNumber::format((float) $purchaseRequest->total_amount, 2) }}</td></tr>
        </tbody>
    </table>

    @if ($purchaseRequest->notes)
        <div class="section-title">{{ __('ui.common.notes') }}</div>
        <div class="note">{{ $purchaseRequest->notes }}</div>
    @endif

    <table class="signatures">
        <tr><td>Diajukan Oleh<div class="sign-line"></div>{{ $purchaseRequest->requester?->name ?? '—' }}</td><td>Disetujui Oleh<div class="sign-line"></div>{{ $purchaseRequest->reviewer?->name ?? ' ' }}</td></tr>
    </table>
    <div class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }}. Nilai adalah estimasi berdasarkan harga yang dimasukkan pada Purchase Request.</div>
</body>
</html>
