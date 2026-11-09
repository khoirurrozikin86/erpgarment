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
        <h1>Material Request</h1>
        <div class="muted">Permintaan kebutuhan barang internal</div>
        <div class="document-no">{{ $materialRequest->number }}</div>
    </div>

    <table class="meta">
        <tr><td class="label">Pemohon</td><td>{{ $materialRequest->requester?->name ?? 'User dihapus' }}</td><td class="label">Tanggal Pengajuan</td><td>{{ $materialRequest->created_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">Departemen</td><td>{{ $materialRequest->department ?: '—' }}</td><td class="label">Tanggal Dibutuhkan</td><td>{{ $materialRequest->needed_at?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="label">Status Approval</td><td>{{ ['pending' => 'Menunggu Approval', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$materialRequest->status] ?? $materialRequest->status }}</td><td class="label">Progress Purchasing</td><td>{{ ['not_processed' => 'Belum Diproses', 'partially_processed' => 'Sebagian Dialokasikan ke PR', 'processed' => 'Seluruh Kebutuhan Dialokasikan ke PR'][$materialRequest->purchase_status] ?? '—' }}</td></tr>
        <tr><td class="label">Ditinjau Oleh</td><td>{{ $materialRequest->reviewer?->name ?? '—' }}</td><td class="label">Waktu Review</td><td>{{ $materialRequest->reviewed_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
    </table>

    <div class="section-title">Barang yang Diminta</div>
    <table class="items">
        <thead><tr><th class="center" style="width:32px">No</th><th style="width:110px">Kode</th><th>Nama Barang</th><th class="right" style="width:100px">Jumlah</th><th style="width:85px">Satuan</th><th>Catatan</th></tr></thead>
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
                <tr><td colspan="6" class="center">Tidak ada barang.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Alasan Permintaan</div>
    <div class="note">{{ $materialRequest->reason }}</div>

    @if ($materialRequest->review_note)
        <div class="section-title">Catatan Approval</div>
        <div class="note">{{ $materialRequest->review_note }}</div>
    @endif

    <table class="signatures">
        <tr><td>Pemohon<div class="sign-line"></div>{{ $materialRequest->requester?->name ?? '—' }}</td><td>Persetujuan<div class="sign-line"></div>{{ $materialRequest->reviewer?->name ?? ' ' }}</td></tr>
    </table>
    <div class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }}.</div>
</body>
</html>
