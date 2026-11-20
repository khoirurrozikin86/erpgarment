<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $materialRequest->number }}</title>
    <style>
        @page { margin: 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #202938; font-size: 11px; }
        h1 { font-size: 17px; margin: 0 0 2px; }
        .company-header { width: 100%; border-bottom: 1px solid #cfd5df; padding-bottom: 5px; margin-bottom: 7px; }
        .company-logo-cell { width: 72px; vertical-align: middle; }
        .company-logo-cell img { max-width: 62px; max-height: 42px; }
        .company-info { vertical-align: middle; line-height: 1.25; }
        .company-name { font-size: 14px; font-weight: bold; color: #263b5e; margin-bottom: 2px; }
        .company-contact { color: #475467; }
        .header { border-bottom: 1px solid #263b5e; padding-bottom: 5px; margin-bottom: 6px; }
        .document-no { font-size: 13px; font-weight: bold; color: #263b5e; }
        .meta { width: 100%; margin-bottom: 6px; }
        .meta td { padding: 2px 6px 2px 0; vertical-align: top; }
        .meta .label { width: 145px; color: #667085; }
        .section-title { font-size: 12px; font-weight: bold; margin: 8px 0 4px; }
        table.items { border-collapse: collapse; width: 100%; }
        .items th, .items td { border: 1px solid #cfd5df; padding: 3px 5px; }
        .items th { background: #f1f4f8; text-align: left; }
        .items .size, .items .color { width: 58px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .note { margin: 5px 0 0; }
        .note-text { white-space: pre-line; }
        .signatures { width: 100%; margin-top: 10px; }
        .signatures td { width: 50%; text-align: center; padding: 3px 18px; }
        .sign-line { border-bottom: 1px solid #475467; height: 30px; margin-bottom: 3px; }
        .footer { margin-top: 8px; border-top: 1px solid #d0d5dd; padding-top: 3px; font-size: 9px; color: #667085; }
    </style>
</head>
<body>
    @include('super.documents.partials.company-pdf-header', ['company' => $company, 'companyLogoDataUri' => $companyLogoDataUri])
    <div class="header">
        <h1>{{ __('ui.common.material_request') }}</h1>
        <div class="document-no">{{ $materialRequest->number }}</div>
    </div>

    <table class="meta">
        <tr><td class="label">{{ __('ui.common.requester') }}</td><td>{{ $materialRequest->requester?->name ?? 'User dihapus' }}</td><td class="label">{{ __('ui.common.request_date') }}</td><td>{{ $materialRequest->created_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">{{ __('ui.common.department') }}</td><td>{{ $materialRequest->department ?: '—' }}</td><td class="label">{{ __('ui.common.needed_date') }}</td><td>{{ $materialRequest->needed_at?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="label">{{ __('ui.common.approval_status') }}</td><td>{{ ['pending' => 'Menunggu Approval', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$materialRequest->status] ?? $materialRequest->status }}</td><td class="label">{{ __('ui.common.purchasing_progress') }}</td><td>{{ ['not_processed' => 'Belum Diproses', 'partially_processed' => 'Sebagian Dialokasikan ke PR', 'processed' => 'Seluruh Kebutuhan Dialokasikan ke PR'][$materialRequest->purchase_status] ?? '—' }}</td></tr>
        @if ($materialRequest->bom_version)
            <tr><td class="label">{{ __('ui.workflow.bom_used') }}</td><td>{{ $materialRequest->billOfMaterial?->number ?? 'BOM '.$materialRequest->bom_version }}{{ $materialRequest->billOfMaterial ? ' — '.__('variants.bom_type_'.$materialRequest->billOfMaterial->bom_type) : '' }}</td><td class="label">{{ __('ui.workflow.material_order') }}</td><td>{{ $materialRequest->productionOrder?->number ?? '—' }} · {{ $materialRequest->outputProduct?->variant_display_name ?? '—' }} × {{ number_format((float) $materialRequest->production_quantity, 4, ',', '.') }}</td></tr>
        @endif
        <tr><td class="label">{{ __('ui.common.reviewed_by') }}</td><td>{{ $materialRequest->reviewer?->name ?? '—' }}</td><td class="label">{{ __('ui.common.review_time') }}</td><td>{{ $materialRequest->reviewed_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
    </table>

    @if ($productionDetails->isNotEmpty())
        <div class="section-title">{{ __('ui.nav.styles') }} / {{ __('ui.workflow.color') }} / {{ __('ui.workflow.size') }} / {{ __('ui.workflow.order_quantity') }}</div>
        @foreach ($productionDetails as $detail)
            <div>{{ $detail['style'] ?: '—' }} / {{ $detail['color'] ?: '—' }} / {{ $detail['size'] ?: '—' }} / {{ $detail['quantity'] === null ? '—' : number_format((float) $detail['quantity'], 4, ',', '.') }}</div>
        @endforeach
    @endif

    <div class="section-title">Barang yang Diminta</div>
    <table class="items">
        <thead><tr><th class="center" style="width:32px">{{ __('ui.common.no') }}</th><th style="width:90px">{{ __('ui.common.code') }}</th><th>{{ __('ui.common.item_name') }}</th><th class="size">Size</th><th class="color">Color</th><th class="right" style="width:85px">{{ __('ui.common.quantity') }}</th><th style="width:55px">{{ __('ui.common.unit') }}</th><th>{{ __('ui.common.notes') }}</th></tr></thead>
        <tbody>
            @forelse ($pdfItems as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item['code'] }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['size'] ?: '—' }}</td>
                    <td>{{ $item['color'] ?: '—' }}</td>
                    <td class="right">{{ number_format((float) $item['quantity'], 4, ',', '.') }}</td>
                    <td>{{ $item['unit'] }}</td>
                    <td>{{ $item['note'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="center">{{ __('ui.common.no_items') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="note"><strong>{{ __('ui.common.request_reason') }}</strong> <span class="note-text">{{ $materialRequest->reason }}</span></p>

    @if ($materialRequest->review_note)
        <p class="note"><strong>{{ __('ui.common.approval_notes') }}</strong> <span class="note-text">{{ $materialRequest->review_note }}</span></p>
    @endif

    <table class="signatures">
        <tr><td>{{ __('ui.common.requester') }}<div class="sign-line"></div>{{ $materialRequest->requester?->name ?? '—' }}</td><td>Persetujuan<div class="sign-line"></div>{{ $materialRequest->reviewer?->name ?? ' ' }}</td></tr>
    </table>
    <div class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }}.</div>
</body>
</html>
