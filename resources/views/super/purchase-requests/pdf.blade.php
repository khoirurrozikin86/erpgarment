<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $purchaseRequest->number }}</title>
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
        .note { border: 1px solid #cfd5df; min-height: 40px; padding: 8px; white-space: pre-line; }
        .total { font-weight: bold; background: #f1f4f8; }
        .signatures { width: 100%; margin-top: 38px; }
        .signatures td { width: 50%; text-align: center; padding: 8px 18px; }
        .sign-line { border-bottom: 1px solid #475467; height: 48px; margin-bottom: 6px; }
        .footer { margin-top: 20px; border-top: 1px solid #d0d5dd; padding-top: 7px; font-size: 9px; color: #667085; }
    </style>
</head>
<body>
    @include('super.documents.partials.company-pdf-header', ['company' => $company, 'companyLogoDataUri' => $companyLogoDataUri])
    <div class="header">
        <h1>Purchase Request</h1>
        <div class="muted">Permintaan persetujuan pembelian barang</div>
        <div class="document-no">{{ $purchaseRequest->number }}</div>
    </div>

    <table class="meta">
        <tr><td class="label">Referensi Material Request</td><td>{{ $purchaseRequest->materialRequest?->number ?? '—' }}</td><td class="label">Tanggal Pengajuan</td><td>{{ $purchaseRequest->created_at?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="label">Supplier</td><td>{{ $purchaseRequest->supplier?->name ?? 'Supplier dihapus' }}</td><td class="label">Pemohon</td><td>{{ $purchaseRequest->requester?->name ?? 'User dihapus' }}</td></tr>
        <tr><td class="label">Status</td><td>{{ ['pending' => 'Menunggu Approval', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$purchaseRequest->status] ?? $purchaseRequest->status }}</td><td class="label">Ditinjau Oleh</td><td>{{ $purchaseRequest->reviewer?->name ?? '—' }}</td></tr>
        <tr><td class="label">Waktu Review</td><td>{{ $purchaseRequest->reviewed_at?->format('d/m/Y H:i') ?? '—' }}</td><td class="label">Catatan Review</td><td>{{ $purchaseRequest->review_note ?: '—' }}</td></tr>
    </table>

    <div class="section-title">Rincian Pembelian</div>
    <table class="items">
        <thead><tr><th class="center" style="width:32px">No</th><th style="width:105px">Kode</th><th>Nama Barang</th><th class="right" style="width:75px">Jumlah</th><th style="width:65px">Satuan</th><th class="right" style="width:105px">Harga Satuan</th><th class="right" style="width:110px">Subtotal</th></tr></thead>
        <tbody>
            @forelse ($purchaseRequest->items as $item)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->code ?? '—' }}</td>
                    <td>{{ $item->product?->name ?? 'Barang dihapus' }}</td>
                    <td class="right">{{ number_format((float) $item->quantity, 4, ',', '.') }}</td>
                    <td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—' }}</td>
                    <td class="right">Rp {{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((float) $item->line_total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center">Tidak ada barang.</td></tr>
            @endforelse
            <tr class="total"><td colspan="6" class="right">Total Estimasi</td><td class="right">Rp {{ number_format((float) $purchaseRequest->total_amount, 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    @if ($purchaseRequest->notes)
        <div class="section-title">Catatan</div>
        <div class="note">{{ $purchaseRequest->notes }}</div>
    @endif

    <table class="signatures">
        <tr><td>Diajukan Oleh<div class="sign-line"></div>{{ $purchaseRequest->requester?->name ?? '—' }}</td><td>Disetujui Oleh<div class="sign-line"></div>{{ $purchaseRequest->reviewer?->name ?? ' ' }}</td></tr>
    </table>
    <div class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }}. Nilai adalah estimasi berdasarkan harga yang dimasukkan pada Purchase Request.</div>
</body>
</html>
