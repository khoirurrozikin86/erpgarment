<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota {{ $sale->number }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 3mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            width: 74mm;
            margin: 0 auto;
            color: #111;
            background: #fff;
            font: 12px/1.35 Arial, sans-serif;
        }

        .receipt {
            padding: 3mm 0 5mm;
        }

        .center {
            text-align: center;
        }

        .logo {
            display: block;
            max-width: 22mm;
            max-height: 16mm;
            margin: 0 auto 2mm;
            object-fit: contain;
        }

        .company {
            font-size: 15px;
            font-weight: 700;
        }

        .muted {
            color: #444;
        }

        .rule {
            border: 0;
            border-top: 1px dashed #333;
            margin: 3mm 0;
        }

        .meta {
            display: flex;
            justify-content: space-between;
            gap: 3mm;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
        }

        .items td {
            padding: 1.3mm 0;
            vertical-align: top;
        }

        .items .description {
            padding-right: 2mm;
        }

        .items .amount {
            text-align: right;
            white-space: nowrap;
        }

        .totals div {
            display: flex;
            justify-content: space-between;
            gap: 3mm;
            padding: .7mm 0;
        }

        .grand {
            font-size: 14px;
            font-weight: 700;
        }

        .void {
            margin: 3mm 0;
            padding: 2mm;
            border: 1px solid #111;
            text-align: center;
            font-weight: 700;
        }

        .actions {
            display: flex;
            justify-content: center;
            margin: 5mm 0;
        }

        .actions button {
            padding: 2mm 5mm;
            border: 1px solid #333;
            background: #fff;
            font-size: 13px;
        }

        @media screen {
            body {
                margin: 18px auto;
                padding: 0 10px;
                box-shadow: 0 2px 18px #0002;
            }

            .receipt {
                padding: 5mm 0;
            }
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                width: 74mm;
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <main class="receipt">
        <header class="center">
            @if ($companyLogoDataUri)
                <img class="logo" src="{{ $companyLogoDataUri }}" alt="Logo">
            @endif
            <div class="company">{{ $company?->name ?? 'Kasir POS' }}</div>
            @if ($company?->address)
                <div>{{ $company->address }}</div>
            @endif
            @if ($company?->phone)
                <div>Telp. {{ $company->phone }}</div>
            @endif
            @if ($company?->receipt_header)
                <div>{{ $company->receipt_header }}</div>
            @endif
        </header>

        <hr class="rule">
        @if ($sale->status === 'voided')
            <div class="void">TRANSAKSI VOID</div>
        @endif
        <div class="meta"><span>No.</span><strong>{{ $sale->number }}</strong></div>
        <div class="meta"><span>Tanggal</span><span>{{ $sale->sold_at?->format('d/m/Y H:i') }}</span></div>
        <div class="meta"><span>Kasir</span><span>{{ $sale->cashier?->name ?? '—' }}</span></div>
        <div class="meta"><span>Sesi</span><span>{{ $sale->session?->number ?? '—' }}</span></div>
        <div class="meta"><span>Customer</span><span>{{ $sale->customer?->name ?? 'Umum' }}</span></div>
        <hr class="rule">

        <table class="items">
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td class="description" colspan="2">{{ $item->product?->name ?? 'Barang dihapus' }}<br><span
                                class="muted">{{ number_format((float) $item->quantity, 4, ',', '.') }}
                                {{ $item->product?->unit?->symbol ?: $item->product?->unit?->name }} × Rp
                                {{ number_format((float) $item->unit_price, 0, ',', '.') }}</span></td>
                        <td class="amount">Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                    </tr>
                    @if ((float) $item->discount_amount > 0)
                        <tr>
                            <td class="description muted" colspan="2">Diskon barang</td>
                            <td class="amount">-Rp {{ number_format((float) $item->discount_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <hr class="rule">

        <section class="totals">
            <div><span>Subtotal</span><span>Rp {{ number_format((float) $sale->subtotal, 0, ',', '.') }}</span></div>
            @if ((float) $sale->discount_amount > 0)
                <div><span>Diskon</span><span>-Rp
                        {{ number_format((float) $sale->discount_amount, 0, ',', '.') }}</span></div>
            @endif
            @if ((float) $sale->tax_amount > 0)
                <div><span>Pajak</span><span>Rp {{ number_format((float) $sale->tax_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="grand"><span>TOTAL</span><span>Rp
                    {{ number_format((float) $sale->total_amount, 0, ',', '.') }}</span></div>
            <div>
                <span>Metode</span><span>{{ ['cash' => 'Tunai', 'bank_transfer' => 'Transfer', 'card' => 'Kartu'][$sale->payment_method] ?? $sale->payment_method }}</span>
            </div>
            <div><span>Dibayar</span><span>Rp {{ number_format((float) $sale->paid_amount, 0, ',', '.') }}</span></div>
            @if ((float) $sale->change_amount > 0)
                <div><span>Kembalian</span><span>Rp
                        {{ number_format((float) $sale->change_amount, 0, ',', '.') }}</span></div>
            @endif
        </section>

        @if ($sale->status === 'voided')
            <hr class="rule">
            <div>Alasan void: {{ $sale->void_reason }}</div>
            <div class="muted">Diproses {{ $sale->voided_at?->format('d/m/Y H:i') }} oleh
                {{ $sale->voider?->name ?? '—' }}</div>
        @endif

        <hr class="rule">
        <footer class="center">
            @if ($company?->receipt_footer)
                <div>{{ $company->receipt_footer }}</div>
            @endif
            <div>Terima kasih atas kunjungan Anda.</div>
        </footer>
        <div class="actions"><button type="button" onclick="window.print()">Cetak Nota</button></div>
    </main>
    @if ($autoPrint)
        <script>
            window.addEventListener('afterprint', () => window.close(), {
                once: true
            });
            window.addEventListener('load', () => {
                window.setTimeout(() => window.print(), 250);
            });
        </script>
    @endif
</body>

</html>
