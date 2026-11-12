<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $materialRequest->number }}</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #202938; font-size: 11px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #667085; }
        .company-header { width: 100%; border-bottom: 1px solid #cfd5df; padding-bottom: 10px; margin-bottom: 16px; }
        .company-logo-cell { width: 90px; vertical-align: middle; }
        .company-logo-cell img { max-width: 76px; max-height: 62px; }
        .company-info { vertical-align: middle; line-height: 1.5; }
        .company-name { font-size: 16px; font-weight: bold; color: #263b5e; margin-bottom: 2px; }
        .company-contact { color: #475467; }
        .header { border-bottom: 2px solid #263b5e; padding-bottom: 14px; margin-bottom: 18px; }
        .document-no { font-size: 13px; font-weight: bold; color: #263b5e; }
        .meta { width: 100%; margin-bottom: 18px; }
        .meta td { padding: 4px 8px 4px 0; vertical-align: top; }
        .meta .label { width: 145px; color: #667085; }
        .section-title { font-size: 12px; font-weight: bold; margin: 16px 0 8px; }
        table.items { border-collapse: collapse; width: 100%; }
        .items th, .items td { border: 1px solid #cfd5df; padding: 7px 8px; }
        .items th { background: #f1f4f8; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .note { border: 1px solid #cfd5df; min-height: 44px; padding: 8px; white-space: pre-line; }
        .signatures { width: 100%; margin-top: 38px; }
        .signatures td { width: 50%; text-align: center; padding: 8px 18px; }
        .sign-line { border-bottom: 1px solid #475467; height: 48px; margin-bottom: 6px; }
        .footer { margin-top: 20px; border-top: 1px solid #d0d5dd; padding-top: 7px; font-size: 9px; color: #667085; }
    </style>
</head>
<body>
    @include('super.documents.partials.company-pdf-header', ['company' => $company, 'companyLogoDataUri' => $companyLogoDataUri])
    <div class="header">
        <h1>{{ __('ui.common.material_request') }}</h1>
        <div class="muted">Permintaan kebutuhan barang internal</div>
        <div class="document-no">{{ $materialRequest->number }}</div>
    </div>

    <table class="meta">
        <tr><td class="label">{{ __('ui.common.requester') }}</td><td>{{ $materialRequest->requester?->name ?? 'User dihapus' }}</td><td class="label">{{ __('ui.common.request_date') }}</td><td>{{ $materialRequest->created_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">{{ __('ui.common.department') }}</td><td>{{ $materialRequest->department ?: '—' }}</td><td class="label">{{ __('ui.common.needed_date') }}</td><td>{{ $materialRequest->needed_at?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="label">{{ __('ui.common.approval_status') }}</td><td>{{ ['pending' => 'Menunggu Approval', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$materialRequest->status] ?? $materialRequest->status }}</td><td class="label">{{ __('ui.common.purchasing_progress') }}</td><td>{{ ['not_processed' => 'Belum Diproses', 'partially_processed' => 'Sebagian Dialokasikan ke PR', 'processed' => 'Seluruh Kebutuhan Dialokasikan ke PR'][$materialRequest->purchase_status] ?? '—' }}</td></tr>
        @if ($materialRequest->bom_version)
            <tr><td class="label">{{ __('ui.workflow.bom_used') }}</td><td>{{ $materialRequest->outputProduct?->name ?? '—' }} × {{ number_format((float) $materialRequest->production_quantity, 4, ',', '.') }}</td><td class="label">{{ __('ui.workflow.production_order') }}</td><td>{{ $materialRequest->productionOrder?->number ?? 'BOM ' . $materialRequest->bom_version }}</td></tr>
        @endif
        <tr><td class="label">{{ __('ui.common.reviewed_by') }}</td><td>{{ $materialRequest->reviewer?->name ?? '—' }}</td><td class="label">{{ __('ui.common.review_time') }}</td><td>{{ $materialRequest->reviewed_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
    </table>

    <div class="section-title">Barang yang Diminta</div>
    <table class="items">
        <thead><tr><th class="center" style="width:32px">{{ __('ui.common.no') }}</th><th style="width:110px">{{ __('ui.common.code') }}</th><th>{{ __('ui.common.item_name') }}</th><th class="right" style="width:100px">{{ __('ui.common.quantity') }}</th><th style="width:85px">{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.notes') }}</th></tr></thead>
        <tbody>
            @forelse ($materialRequest->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->code ?? '—' }}</td>
                    <td>{{ $item->product?->name ?? 'Barang dihapus' }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 4, ',', '.') }}</td>
                    <td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—' }}</td>
                    <td>{{ $item->note ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">{{ __('ui.common.no_items') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">{{ __('ui.common.request_reason') }}</div>
    <div class="note">{{ $materialRequest->reason }}</div>

    @if ($materialRequest->review_note)
        <div class="section-title">{{ __('ui.common.approval_notes') }}</div>
        <div class="note">{{ $materialRequest->review_note }}</div>
    @endif

    <table class="signatures">
        <tr><td>{{ __('ui.common.requester') }}<div class="sign-line"></div>{{ $materialRequest->requester?->name ?? '—' }}</td><td>Persetujuan<div class="sign-line"></div>{{ $materialRequest->reviewer?->name ?? ' ' }}</td></tr>
    </table>
    <div class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }}.</div>
</body>
</html>
