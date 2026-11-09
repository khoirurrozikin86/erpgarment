<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $customerReturn->number }}</title>
    <style>
        @page {
            margin: 32px 36px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #202938;
            font-size: 11px;
        }

        .company-header {
            width: 100%;
            border-bottom: 1px solid #cfd5df;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .company-logo-cell {
            width: 60px;
            vertical-align: middle;
        }

        .company-logo-cell img {
            max-width: 48px;
            max-height: 40px;
        }

        .company-info {
            vertical-align: middle;
            line-height: 1.5;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }

        .muted {
            color: #667085;
        }

        .header {
            border-bottom: 2px solid #263b5e;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .document-no {
            font-size: 13px;
            font-weight: bold;
            color: #263b5e;
        }

        .meta {
            width: 100%;
            margin-bottom: 18px;
        }

        .meta td {
            padding: 4px 8px 4px 0;
            vertical-align: top;
        }

        .meta .label {
            width: 130px;
            color: #667085;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin: 16px 0 8px;
        }

        table.items {
            border-collapse: collapse;
            width: 100%;
        }

        .items th,
        .items td {
            border: 1px solid #cfd5df;
            padding: 7px 8px;
        }

        .items th {
            background: #f1f4f8;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .total {
            font-weight: bold;
            background: #f1f4f8;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #d0d5dd;
            padding-top: 7px;
            font-size: 9px;
            color: #667085;
        }
    </style>
</head>

<body>
    @include('super.documents.partials.company-pdf-header', [
        'company' => $company,
        'companyLogoDataUri' => $companyLogoDataUri,
    ])
    <div class="header">
        <h1>Customer Return</h1>
        <div class="muted">Retur barang dan refund kepada customer</div>
        <div class="document-no">{{ $customerReturn->number }}</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Nomor Invoice</td>
            <td>{{ $customerReturn->invoice?->number ?? '—' }}</td>
            <td class="label">Tanggal Retur</td>
            <td>{{ $customerReturn->returned_at?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Customer</td>
            <td>{{ $customerReturn->customer?->name ?? 'Customer dihapus' }}</td>
            <td class="label">Akun Refund</td>
            <td>{{ $customerReturn->cashBankAccount?->code }} —
                {{ $customerReturn->cashBankAccount?->name ?? 'Akun dihapus' }}</td>
        </tr>
        <tr>
            <td class="label">Dicatat Oleh</td>
            <td>{{ $customerReturn->returner?->name ?? 'User dihapus' }}</td>
            <td class="label">Alasan</td>
            <td>{{ $customerReturn->reason }}</td>
        </tr>
    </table>

    <div class="section-title">Rincian Barang Retur</div>
    <table class="items">
        <thead>
            <tr>
                <th class="center" style="width:32px">No</th>
                <th style="width:110px">Kode</th>
                <th>Nama Barang</th>
                <th class="right" style="width:90px">Jumlah</th>
                <th style="width:70px">Satuan</th>
                <th class="right" style="width:105px">Harga Satuan</th>
                <th class="right" style="width:110px">Nilai Retur</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($customerReturn->items as $item)
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
                <tr>
                    <td colspan="7" class="center">Tidak ada barang retur.</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="6" class="right">Subtotal</td>
                <td class="right">Rp {{ number_format((float) $customerReturn->subtotal, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="6" class="right">Pajak</td>
                <td class="right">Rp {{ number_format((float) $customerReturn->tax_amount, 2, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td colspan="6" class="right">Total Refund</td>
                <td class="right">Rp {{ number_format((float) $customerReturn->total_amount, 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    @if ($customerReturn->notes)
        <div class="section-title">Catatan</div>
        <div>{{ $customerReturn->notes }}</div>
    @endif
    <div class="footer">Customer Return {{ $customerReturn->number }} · Dicetak pada {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>

</html>
