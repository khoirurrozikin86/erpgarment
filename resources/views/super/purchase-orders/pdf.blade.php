<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $purchaseOrder->number }}</title>
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
        .meta .label { width: 150px; color: #667085; }
        .section-title { font-size: 12px; font-weight: bold; margin: 16px 0 8px; }
        table.items { border-collapse: collapse; width: 100%; }
        .items th, .items td { border: 1px solid #cfd5df; padding: 7px 8px; }
        .items th { background: #f1f4f8; text-align: left; }
        .right { text-align: right; }
        .center { text-align: center; }
        .note { border: 1px solid #cfd5df; min-height: 40px; padding: 8px; white-space: pre-line; }
        .total { font-weight: bold; background: #f1f4f8; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; padding: 8px 18px; }
        .sign-line { border-bottom: 1px solid #475467; height: 55px; margin-bottom: 6px; }
        .footer { margin-top: 20px; border-top: 1px solid #d0d5dd; padding-top: 7px; font-size: 9px; color: #667085; }
    </style>
</head>
<body>
    @include('super.documents.partials.company-pdf-header', ['company' => $company, 'companyLogoDataUri' => $companyLogoDataUri])
    <div class="header">
        <h1>Purchase Order</h1>
        <div class="muted">Pesanan pembelian kepada supplier</div>
        <div class="document-no">{{ $purchaseOrder->number }}</div>
    </div>

    <table class="meta">
        <tr><td class="label">Supplier</td><td>{{ $purchaseOrder->supplier?->name ?? 'Supplier dihapus' }}</td><td class="label">Referensi Purchase Request</td><td>{{ $purchaseOrder->purchaseRequest?->number ?? '—' }}</td></tr>
        <tr><td class="label">Alamat Supplier</td><td>{{ $purchaseOrder->supplier?->address ?: '—' }}</td><td class="label">Tanggal PO</td><td>{{ $purchaseOrder->order_date?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="label">Telepon Supplier</td><td>{{ $purchaseOrder->supplier?->phone ?: '—' }}</td><td class="label">Perkiraan Tiba</td><td>{{ $purchaseOrder->expected_delivery_at?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><td class="label">Email Supplier</td><td>{{ $purchaseOrder->supplier?->email ?: '—' }}</td><td class="label">Status</td><td>{{ ['draft' => 'Draft', 'issued' => 'Diterbitkan', 'partially_received' => 'Diterima Sebagian', 'received' => 'Selesai Diterima', 'cancelled' => 'Dibatalkan'][$purchaseOrder->status] ?? $purchaseOrder->status }}</td></tr>
        <tr><td class="label">Syarat Pembayaran</td><td>{{ $purchaseOrder->payment_terms ?: '—' }}</td><td class="label">Tanggal Terbit</td><td>{{ $purchaseOrder->issued_at?->format('d/m/Y H:i') ?? 'Belum diterbitkan' }}</td></tr>
    </table>

    <div class="section-title">Rincian Pesanan</div>
    <table class="items">
        <thead><tr><th class="center" style="width:32px">No</th><th style="width:105px">Kode</th><th>Nama Barang</th><th class="right" style="width:75px">Jumlah</th><th style="width:65px">Satuan</th><th class="right" style="width:105px">Harga Satuan</th><th class="right" style="width:110px">Subtotal</th></tr></thead>
        <tbody>
            @forelse ($purchaseOrder->items as $item)
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
            <tr class="total"><td colspan="6" class="right">Total Pesanan</td><td class="right">Rp {{ number_format((float) $purchaseOrder->total_amount, 2, ',', '.') }}</td></tr>
        </tbody>
    </table>

    @if ($purchaseOrder->notes)
        <div class="section-title">Catatan</div>
        <div class="note">{{ $purchaseOrder->notes }}</div>
    @endif

    <table class="signatures">
        <tr><td>Supplier<div class="sign-line"></div>{{ $purchaseOrder->supplier?->name ?? '—' }}</td><td>Disiapkan Oleh<div class="sign-line"></div>{{ $purchaseOrder->creator?->name ?? '—' }}</td></tr>
    </table>
    <div class="footer">PO {{ $purchaseOrder->number }} · Dicetak pada {{ now()->format('d/m/Y H:i') }}.</div>
</body>
</html>
