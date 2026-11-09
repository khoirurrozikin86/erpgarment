@extends('layouts.admin')
@section('title', 'Kasir POS')
@section('breadcrumb')@endsection
@section('content')
    <div class="pos-app">
        <header class="pos-screen-header">
            <div class="d-flex align-items-center gap-3"><a href="{{ route('super.dashboard') }}" class="pos-back-button"
                    title="Kembali"><i data-feather="arrow-left"></i></a><strong class="pos-brand">Kasir</strong><span
                    class="pos-order-tab">Order <b>{{ $session?->number ?? 'Baru' }}</b></span></div>
            <div class="pos-header-actions"><label class="pos-search-box"><i data-feather="search"></i><input
                        id="posProductSearch" type="search" placeholder="Cari nama, kode, atau barcode barang..."
                        {{ $session ? '' : 'disabled' }}></label><a href="{{ route('super.pos-sessions.index') }}"
                    class="pos-session-link"><i data-feather="clock"></i><span>Sesi Kasir</span></a><button type="button"
                    id="posHistoryButton" class="pos-session-link btn btn-link p-0 text-decoration-none"><i
                        data-feather="list"></i><span>Riwayat</span></button></div>
        </header>
        @if (!$session)
            <div class="alert alert-warning d-flex justify-content-between align-items-center m-3"><span>Belum ada sesi
                    kasir terbuka untuk akun Anda. Buka sesi sebelum mulai menjual.</span><a
                    href="{{ route('super.pos-sessions.index') }}" class="btn btn-sm btn-warning">Buka Sesi</a></div>
        @else
            <div class="pos-session-strip"><span><i data-feather="unlock"></i>Sesi aktif
                    <strong>{{ $session->number }}</strong> · {{ $session->cashBankAccount?->name }}</span><span>Saldo awal
                    <strong>Rp {{ number_format($session->opening_cash, 0, ',', '.') }}</strong></span></div>
            <main class="pos-workspace">
                <section class="pos-order-panel">
                    <div class="pos-panel-heading">
                        <div><span class="pos-eyebrow">PESANAN AKTIF</span>
                            <h5 class="mb-0">Order {{ $session->number }}</h5>
                        </div><button type="button" id="clearPosCart" class="btn btn-sm btn-light text-danger"
                            title="Kosongkan pesanan"><i data-feather="trash-2"></i></button>
                    </div>
                    <div id="posCartRows" class="pos-cart-list">
                        <div class="pos-empty-cart"><i data-feather="shopping-cart"></i><span>Belum ada barang di
                                pesanan</span><small>Pilih barang dari katalog untuk mulai</small></div>
                    </div>
                    <div class="pos-order-footer">
                        <div class="pos-discount-row"><label for="posDiscountRate">Diskon pesanan (%)</label><input
                                id="posDiscountRate" type="number" min="0" max="100" step="0.01"
                                value="0" class="form-control form-control-sm"></div>
                        <div class="pos-total-line"><span>Subtotal</span><strong id="posSubtotal">Rp 0</strong></div>
                        <div class="pos-total-line"><span>Diskon</span><strong id="posDiscount">Rp 0</strong></div>
                        <div class="pos-total-line"><span>Pajak</span><strong id="posTax">Rp 0</strong></div>
                        <div class="pos-grand-total"><span>Total</span><strong id="posTotal" data-value="0">Rp 0</strong>
                        </div>
                        <label class="pos-payment-label">Metode pembayaran</label><input type="hidden"
                            id="posPaymentMethod" value="cash">
                        <div class="pos-payment-methods" id="posPaymentMethods">
                            <button type="button" class="pos-method-button active" data-method="cash"><i
                                    data-feather="dollar-sign"></i>Tunai</button>
                            <button type="button" class="pos-method-button" data-method="bank_transfer"><i
                                    data-feather="shuffle"></i>Transfer</button>
                            <button type="button" class="pos-method-button" data-method="card"><i
                                    data-feather="credit-card"></i>Kartu</button>
                        </div>
                        <div id="posPaymentAccountWrap" class="d-none"><label for="posPaymentAccount"
                                class="form-label small mb-1">Akun pembayaran</label><select id="posPaymentAccount"
                                class="form-select form-select-sm">
                                @foreach ($paymentAccounts as $account)
                                    <option value="{{ $account->id }}" data-type="{{ $account->type }}"
                                        {{ $account->id === $session->cash_bank_account_id ? 'selected' : '' }}>
                                        {{ $account->code }} · {{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="posCashReceivedWrap" class="pos-cash-entry">
                            <div class="d-flex justify-content-between align-items-center mb-1"><label for="posCashReceived"
                                    class="form-label mb-0">Uang diterima</label><button type="button" id="posExactCash"
                                    class="btn btn-link btn-sm p-0">Uang pas</button></div><input id="posCashReceived"
                                type="number" min="0" step="0.01" class="form-control"
                                placeholder="Masukkan jumlah uang">
                            <div id="posCashChange" class="small mt-1 text-muted">Kembalian: Rp 0</div>
                        </div>
                        <button type="button" id="posCheckout" class="pos-pay-button"><i
                                data-feather="check-circle"></i><span>Bayar</span><strong id="posPayButtonTotal">Rp
                                0</strong></button>
                    </div>
                </section>
                <section class="pos-catalog-panel">
                    <div class="pos-catalog-toolbar">
                        <div><span class="pos-eyebrow">KATALOG</span>
                            <h5 class="mb-0">Pilih Produk</h5>
                        </div>
                        <div id="posSearchHint" class="small text-muted">Memuat produk...</div>
                    </div>
                    <div id="posCategories" class="pos-category-list"><button type="button"
                            class="pos-category-filter active" data-category="">Semua produk</button>
                        @foreach ($categories as $category)
                            <button type="button" class="pos-category-filter"
                                data-category="{{ $category->id }}">{{ $category->name }}<small>{{ $category->products_count }}</small></button>
                        @endforeach
                    </div>
                    <div id="posProductResults" class="pos-product-grid"></div>
                </section>
            </main>
        @endif
        <div class="modal fade" id="posHistoryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-0">Riwayat Transaksi Kasir</h5><small class="text-muted">Maksimal 50
                                transaksi terakhir milik Anda.</small>
                        </div><button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Transaksi</th>
                                        <th>Tanggal / Sesi</th>
                                        <th>Barang</th>
                                        <th class="text-end">Total</th>
                                        <th>Status</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="posHistoryRows">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">Memuat riwayat...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <style>
        body.pos-screen {
            background: #25272b;
        }

        body.pos-screen .page-content {
            padding: 1rem !important;
        }

        body.pos-screen .pos-app {
            height: calc(100vh - 2rem);
            min-height: 540px;
            margin: -1rem;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #d8dadd;
            border-radius: 22px;
            background: #f6f7f8;
        }

        body.pos-screen .pos-screen-header {
            height: 58px;
            min-height: 58px;
            margin: 0;
            padding: 0 18px;
            background: #fff;
            border-bottom: 1px solid #d8dadd;
        }

        .pos-back-button {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            color: #343a40;
            background: #f1f2f3;
            border-radius: 8px;
        }

        .pos-back-button svg,
        .pos-session-link svg {
            width: 17px;
            height: 17px;
        }

        .pos-brand {
            font-size: 17px;
        }

        .pos-order-tab {
            padding: 8px 12px;
            border-left: 1px solid #e2e3e5;
            color: #4b5055;
        }

        .pos-order-tab b {
            margin-left: 8px;
            padding: 5px 9px;
            border-radius: 5px;
            background: #f2f3f4;
            font-weight: 500;
        }

        .pos-header-actions {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .pos-search-box {
            width: min(340px, 35vw);
            height: 38px;
            padding: 0 11px;
            display: flex;
            align-items: center;
            gap: 9px;
            border: 1px solid #d9dcdf;
            border-radius: 8px;
            background: #fff;
        }

        .pos-search-box svg {
            width: 18px;
            height: 18px;
            color: #555b61;
        }

        .pos-search-box input {
            width: 100%;
            border: 0;
            outline: 0;
            background: transparent;
        }

        .pos-session-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: #555b61;
            text-decoration: none;
            white-space: nowrap;
        }

        .pos-session-strip {
            min-height: 35px;
            padding: 6px 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #62676c;
            font-size: 12px;
            background: #fff;
            border-bottom: 1px solid #e0e1e2;
        }

        .pos-session-strip span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .pos-session-strip svg {
            width: 14px;
            height: 14px;
            color: #16834a;
        }

        .pos-workspace {
            flex: 1;
            min-height: 0;
            display: grid;
            grid-template-columns: minmax(330px, 35%) minmax(0, 65%);
        }

        .pos-order-panel {
            min-height: 0;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-right: 1px solid #d8dadd;
        }

        .pos-panel-heading {
            padding: 13px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e7e8e9;
        }

        .pos-eyebrow {
            color: #8b9095;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .08em;
        }

        .pos-panel-heading h5,
        .pos-catalog-toolbar h5 {
            margin-top: 2px;
            font-size: 16px;
        }

        .pos-cart-list {
            flex: 1;
            min-height: 90px;
            overflow-y: auto;
        }

        .pos-empty-cart {
            height: 100%;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #8b9095;
        }

        .pos-empty-cart svg {
            width: 32px;
            height: 32px;
            opacity: .55;
        }

        .pos-empty-cart small {
            color: #a1a5a9;
        }

        .pos-cart-line {
            min-height: 78px;
            padding: 10px 14px;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 6px 10px;
            border-bottom: 1px solid #eceeef;
        }

        .pos-cart-item-name {
            overflow: hidden;
            color: #282c30;
            font-size: 13px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pos-cart-item-meta {
            color: #858b90;
            font-size: 11px;
        }

        .pos-cart-line-total {
            text-align: right;
            font-size: 13px;
            font-weight: 700;
        }

        .pos-qty-control {
            grid-column: 1 / 3;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .pos-qty-control button {
            width: 27px;
            height: 27px;
            display: grid;
            place-items: center;
            border: 1px solid #dadddf;
            border-radius: 6px;
            background: white;
        }

        .pos-qty-control button svg {
            width: 14px;
            height: 14px;
        }

        .pos-qty-control input {
            width: 65px;
            height: 28px;
            padding: 2px 5px;
            text-align: center;
        }

        .pos-cart-remove {
            margin-left: auto;
            color: #a3a7ab;
            border: 0 !important;
        }

        .pos-order-footer {
            padding: 10px 14px 12px;
            border-top: 1px solid #e5e7e9;
            background: #fff;
        }

        .pos-discount-row,
        .pos-total-line,
        .pos-grand-total {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pos-discount-row {
            margin-bottom: 7px;
            font-size: 12px;
        }

        .pos-discount-row input {
            width: 78px;
        }

        .pos-total-line {
            padding: 2px 0;
            color: #686e73;
            font-size: 12px;
        }

        .pos-grand-total {
            margin-top: 5px;
            padding: 8px 0;
            border-top: 1px solid #e5e7e9;
            font-size: 15px;
        }

        .pos-grand-total strong {
            font-size: 18px;
        }

        .pos-payment-label {
            margin: 5px 0 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .pos-payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
        }

        .pos-method-button {
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: 1px solid #dadddf;
            border-radius: 7px;
            background: #fff;
            color: #50565b;
            font-size: 12px;
            font-weight: 600;
        }

        .pos-method-button svg {
            width: 16px;
            height: 16px;
        }

        .pos-method-button.active {
            color: #fff;
            background: #714b67;
            border-color: #714b67;
        }

        .pos-cash-entry {
            margin-top: 8px;
        }

        .pos-cash-entry input {
            height: 40px;
            font-size: 16px;
        }

        .pos-pay-button {
            width: 100%;
            min-height: 49px;
            margin-top: 9px;
            padding: 7px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 0;
            border-radius: 7px;
            color: white;
            background: #714b67;
            font-size: 14px;
            font-weight: 600;
        }

        .pos-pay-button svg {
            width: 18px;
            height: 18px;
        }

        .pos-pay-button strong {
            font-size: 15px;
        }

        .pos-catalog-panel {
            min-width: 0;
            min-height: 0;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            background: #f3f4f5;
        }

        .pos-catalog-toolbar {
            min-height: 44px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pos-category-list {
            padding: 7px 0 12px;
            display: flex;
            gap: 8px;
            overflow-x: auto;
        }

        .pos-category-filter {
            min-height: 42px;
            padding: 7px 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            border: 1px solid #e0e2e4;
            border-radius: 7px;
            color: #454a4e;
            background: #fff;
            font-size: 12px;
            font-weight: 600;
        }

        .pos-category-filter small {
            padding: 2px 6px;
            border-radius: 10px;
            color: #71767b;
            background: #f0f1f2;
        }

        .pos-category-filter.active {
            border-color: #eea48f;
            background: #f6b59f;
        }

        .pos-product-grid {
            flex: 1;
            min-height: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(126px, 1fr));
            align-content: start;
            gap: 9px;
            overflow-y: auto;
            padding: 1px 3px 8px 1px;
        }

        .pos-product-tile {
            min-width: 0;
            padding: 0;
            overflow: hidden;
            text-align: left;
            color: #25292d;
            background: #fff;
            border: 1px solid #e1e3e5;
            border-bottom: 4px solid #efb29f;
            border-radius: 6px;
            transition: transform .12s ease, box-shadow .12s ease;
        }

        .pos-product-tile:not(:disabled):hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px #20252b1c;
        }

        .pos-product-tile:disabled {
            opacity: .55;
            cursor: not-allowed;
            border-bottom-color: #c9cccf;
        }

        .pos-product-image {
            width: 100%;
            aspect-ratio: 1.2 / 1;
            display: block;
            object-fit: cover;
            background: #e8e9eb;
        }

        .pos-product-no-image {
            width: 100%;
            aspect-ratio: 1.2 / 1;
            display: grid;
            place-items: center;
            color: #a3a8ad;
            background: linear-gradient(135deg, #e9ebed, #f5f6f7);
        }

        .pos-product-no-image svg {
            width: 30px;
            height: 30px;
        }

        .pos-product-title {
            min-height: 35px;
            padding: 6px 7px 0;
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            font-size: 11px;
            line-height: 1.25;
        }

        .pos-product-price {
            padding: 3px 7px 7px;
            font-size: 11px;
            font-weight: 700;
        }

        .pos-no-products {
            grid-column: 1 / -1;
            padding: 45px 12px;
            text-align: center;
            color: #858b90;
        }

        @media (max-width: 900px) {
            body.pos-screen .pos-app {
                height: auto;
                min-height: calc(100vh - 1.5rem);
                overflow: visible;
            }

            .pos-workspace {
                grid-template-columns: 1fr;
            }

            .pos-order-panel {
                order: 2;
                border-right: 0;
                border-top: 1px solid #d8dadd;
            }

            .pos-cart-list {
                max-height: 260px;
            }

            .pos-catalog-panel {
                min-height: 55vh;
                order: 1;
            }
        }

        @media (max-width: 575.98px) {
            body.pos-screen .page-content {
                padding: .75rem !important;
            }

            body.pos-screen .pos-app {
                margin: -.75rem;
                min-height: calc(100vh - 1.5rem);
                border-radius: 13px;
            }

            body.pos-screen .pos-screen-header {
                padding: 0 10px;
            }

            .pos-order-tab {
                display: none;
            }

            .pos-header-actions {
                gap: 8px;
            }

            .pos-search-box {
                width: min(52vw, 240px);
            }

            .pos-session-link span {
                display: none;
            }

            .pos-product-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 6px;
            }

            .pos-catalog-panel {
                padding: 10px 8px;
            }

            .pos-session-strip {
                font-size: 10px;
            }
        }
    </style>
    <script>
        $(function() {
            const session = @json($session ? ['id' => $session->id, 'cash_account_id' => $session->cash_bank_account_id] : null);
            const cart = new Map();
            const productsById = new Map();
            const safe = value => $('<div>').text(value ?? '').html();
            const money = value => new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                maximumFractionDigits: 2
            }).format(Number(value || 0));
            const paymentLabel = {
                cash: 'Tunai',
                bank_transfer: 'Transfer',
                card: 'Kartu'
            };
            const historyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('posHistoryModal'));
            const loadHistory = () => {
                $('#posHistoryRows').html(
                    '<tr><td colspan="6" class="text-center text-muted py-4">Memuat riwayat...</td></tr>');
                $.get(@json(route('super.pos.history'))).done(sales => {
                    if (!sales.length) {
                        $('#posHistoryRows').html(
                            '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada transaksi.</td></tr>'
                            );
                        return;
                    }
                    $('#posHistoryRows').html(sales.map(sale => {
                        const status = sale.status === 'voided' ?
                            `<span class="badge bg-danger">VOID</span><div class="small text-muted">${safe(sale.void_reason)}</div>` :
                            '<span class="badge bg-success">Selesai</span>';
                        const itemLines = sale.items.map(item =>
                                `${safe(item.name)} × ${item.quantity} ${safe(item.unit)}`)
                            .join('<br>');
                        const actions =
                            `<a class="btn btn-sm btn-outline-primary" href="${safe(sale.receipt_url)}?autoprint=1" target="_blank" rel="noopener" title="Cetak ulang nota"><i data-feather="printer" class="icon-sm"></i></a>` +
                            (sale.can_void ?
                                ` <button type="button" class="btn btn-sm btn-outline-danger btn-void-pos-sale" data-url="${safe(sale.void_url)}" data-number="${safe(sale.number)}" title="Void transaksi"><i data-feather="x-circle" class="icon-sm"></i></button>` :
                                '');
                        return `<tr><td class="fw-semibold">${safe(sale.number)}<div class="small text-muted">${paymentLabel[sale.payment_method] || safe(sale.payment_method)}</div></td><td>${safe(sale.sold_at)}<div class="small text-muted">${safe(sale.session)}</div></td><td><details><summary>${sale.items.length} item</summary><div class="small mt-1">${itemLines}</div></details></td><td class="text-end text-nowrap">${money(sale.total)}</td><td>${status}</td><td class="text-end text-nowrap">${actions}</td></tr>`;
                    }).join(''));
                    if (window.feather) feather.replace();
                }).fail(xhr => {
                    const message = xhr.responseJSON?.message ||
                    'Riwayat transaksi tidak dapat dimuat.';
                    $('#posHistoryRows').html(
                        `<tr><td colspan="6" class="text-center text-danger py-4">${safe(message)}</td></tr>`
                        );
                });
            };
            $('#posHistoryButton').on('click', function() {
                historyModal.show();
                loadHistory();
            });
            const currentTotal = () => Number($('#posTotal').data('value') || 0);
            const refreshCashChange = () => {
                if ($('#posPaymentMethod').val() !== 'cash') return;
                const total = currentTotal();
                const paid = Number($('#posCashReceived').val()) || 0;
                const change = paid - total;
                if (paid <= 0) $('#posCashChange').removeClass('text-success text-danger').addClass(
                    'text-muted').text('Kembalian: Rp 0');
                else if (change >= 0) $('#posCashChange').removeClass('text-muted text-danger').addClass(
                    'text-success').text(`Kembalian: ${money(change)}`);
                else $('#posCashChange').removeClass('text-muted text-success').addClass('text-danger').text(
                    `Uang kurang: ${money(Math.abs(change))}`);
            };
            const refreshCart = () => {
                const rows = [...cart.values()];
                if (!rows.length) $('#posCartRows').html(
                    '<div class="pos-empty-cart"><i data-feather="shopping-cart"></i><span>Belum ada barang di pesanan</span><small>Pilih barang dari katalog untuk mulai</small></div>'
                );
                else $('#posCartRows').html(rows.map(item =>
                    `<article class="pos-cart-line"><div><div class="pos-cart-item-name">${safe(item.name)}</div><div class="pos-cart-item-meta">${safe(item.code)} · ${money(item.price)} / ${safe(item.unit)}</div></div><div class="pos-cart-line-total">${money(itemTotal(item))}</div><div class="pos-qty-control"><button type="button" class="pos-cart-minus" data-id="${item.id}" aria-label="Kurangi"><i data-feather="minus"></i></button><input class="form-control form-control-sm pos-cart-quantity" data-id="${item.id}" type="number" min="0.0001" step="0.0001" value="${item.quantity}"><button type="button" class="pos-cart-plus" data-id="${item.id}" aria-label="Tambah"><i data-feather="plus"></i></button><button type="button" class="pos-cart-remove" data-id="${item.id}" title="Hapus"><i data-feather="x"></i></button></div></article>`
                ).join(''));
                const subtotal = rows.reduce((sum, item) => sum + item.quantity * item.price, 0);
                const rate = Math.min(100, Math.max(0, Number($('#posDiscountRate').val()) || 0));
                const discount = rows.reduce((sum, item) => sum + Math.round(item.quantity * item.price *
                    rate) / 100, 0);
                const tax = rows.reduce((sum, item) => {
                    const gross = item.quantity * item.price;
                    const lineDiscount = Math.round(gross * rate) / 100;
                    return sum + (item.taxable ? Math.round((gross - lineDiscount) * item.taxRate) /
                        100 : 0);
                }, 0);
                $('#posSubtotal').text(money(subtotal));
                $('#posDiscount').text(money(discount));
                $('#posTax').text(money(tax));
                $('#posTotal').text(money(subtotal - discount + tax)).data('value', subtotal - discount + tax);
                $('#posPayButtonTotal').text(money(subtotal - discount + tax));
                refreshCashChange();
                if (window.feather) feather.replace();
            };

            function itemTotal(item) {
                const gross = item.quantity * item.price;
                const net = gross - Math.round(gross * (Number($('#posDiscountRate').val()) || 0)) / 100;
                return net + (item.taxable ? Math.round(net * item.taxRate) / 100 : 0);
            }
            let searchTimer;
            const loadProducts = () => {
                const term = $('#posProductSearch').val().trim();
                const categoryId = $('#posCategories .pos-category-filter.active').data('category') || '';
                $('#posSearchHint').text('Memuat produk...');
                $.get(@json(route('super.pos.products')), {
                    q: term,
                    category_id: categoryId
                }).done(data => {
                    if (!data.products.length) {
                        $('#posSearchHint').text('0 produk');
                        $('#posProductResults').html(
                            '<div class="pos-no-products">Produk tidak ditemukan.</div>');
                        return;
                    }
                    $('#posSearchHint').text(`${data.products.length} produk`);
                    $('#posProductResults').html(data.products.map(product => {
                        productsById.set(Number(product.id), product);
                        const disabled = product.track_stock && Number(product.stock) <= 0;
                        const image = product.image ?
                            `<img class="pos-product-image" src="${safe(product.image)}" alt="${safe(product.name)}" loading="lazy">` :
                            '<span class="pos-product-no-image"><i data-feather="image"></i></span>';
                        return `<button type="button" class="pos-product-tile pos-add-product" data-id="${Number(product.id)}" ${disabled ? 'disabled' : ''}>${image}<span class="pos-product-title">${safe(product.name)}</span><span class="pos-product-price">${money(product.price)}${disabled ? ' · Stok habis' : ''}</span></button>`;
                    }).join(''));
                    if (window.feather) feather.replace();
                }).fail(() => {
                    $('#posSearchHint').text('Gagal memuat produk');
                    $('#posProductResults').html(
                        '<div class="pos-no-products">Pencarian produk gagal. Coba muat ulang halaman.</div>'
                    );
                });
            };
            $('#posProductSearch').on('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(loadProducts, 250);
            });
            $('#posCategories').on('click', '.pos-category-filter', function() {
                $('#posCategories .pos-category-filter').removeClass('active');
                $(this).addClass('active');
                loadProducts();
            });
            $(document).on('click', '.pos-add-product', function() {
                const product = productsById.get(Number(this.dataset.id));
                if (!product) return;
                if (!cart.has(product.id)) cart.set(product.id, {
                    ...product,
                    quantity: 1
                });
                else cart.get(product.id).quantity += 1;
                refreshCart();
            });
            $('#posCartRows').on('change', '.pos-cart-quantity', function() {
                const item = cart.get(Number(this.dataset.id));
                const quantity = Number(this.value);
                if (!item) return;
                if (quantity > 0) item.quantity = quantity;
                refreshCart();
            }).on('click', '.pos-cart-minus', function() {
                const item = cart.get(Number(this.dataset.id));
                if (item && item.quantity > 1) item.quantity = Math.max(1, item.quantity - 1);
                else if (item) cart.delete(Number(this.dataset.id));
                refreshCart();
            }).on('click', '.pos-cart-plus', function() {
                const item = cart.get(Number(this.dataset.id));
                if (item) item.quantity += 1;
                refreshCart();
            }).on('click', '.pos-cart-remove', function() {
                cart.delete(Number(this.dataset.id));
                refreshCart();
            });
            $('#posDiscountRate').on('input change', refreshCart);
            $('#clearPosCart').on('click', function() {
                cart.clear();
                $('#posDiscountRate').val(0);
                refreshCart();
            });
            const filterPaymentAccounts = () => {
                const method = $('#posPaymentMethod').val();
                const cashMethod = method === 'cash';
                $('#posCashReceivedWrap').toggle(cashMethod);
                $('#posPaymentAccountWrap').toggleClass('d-none', cashMethod);
                if (cashMethod) $('#posPaymentAccount').val(String(session?.cash_account_id ?? ''));
                else {
                    $('#posPaymentAccount option').each(function() {
                        this.hidden = this.dataset.type !== 'bank';
                    });
                    if ($('#posPaymentAccount option:selected').prop('hidden')) $('#posPaymentAccount').val($(
                        '#posPaymentAccount option[data-type="bank"]').first().val());
                }
            };
            $('#posPaymentMethods').on('click', '.pos-method-button', function() {
                $('#posPaymentMethod').val(this.dataset.method);
                $('#posPaymentMethods .pos-method-button').removeClass('active');
                $(this).addClass('active');
                filterPaymentAccounts();
                refreshCashChange();
            });
            $('#posCashReceived').on('input', refreshCashChange);
            $('#posExactCash').on('click', function() {
                $('#posCashReceived').val(currentTotal().toFixed(2)).trigger('input');
            });
            filterPaymentAccounts();
            const promptVoidSale = (url, number) => {
                Swal.fire({
                    icon: 'warning',
                    title: `Void ${number}?`,
                    input: 'textarea',
                    inputLabel: 'Alasan Void',
                    inputPlaceholder: 'Contoh: salah input barang atau jumlah',
                    inputAttributes: {
                        maxlength: 2000
                    },
                    inputValidator: value => value?.trim() ? undefined : 'Alasan wajib diisi.',
                    showCancelButton: true,
                    confirmButtonText: 'Void dan Refund',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#c0392b',
                    preConfirm: reason => $.ajax({
                        url,
                        method: 'PUT',
                        data: {
                            _token: @json(csrf_token()),
                            reason
                        },
                    }).catch(xhr => Swal.showValidationMessage(xhr.responseJSON?.message || Object
                        .values(xhr.responseJSON?.errors || {}).flat()[0] ||
                        'Void gagal diproses.')),
                }).then(result => {
                    if (result.isConfirmed && result.value?.message) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Transaksi di-void',
                            text: result.value.message
                        });
                        loadHistory();
                    }
                });
            };
            $('#posCheckout').on('click', function() {
                const items = [...cart.values()];
                if (!items.length) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Keranjang kosong',
                        text: 'Tambahkan barang sebelum memproses penjualan.'
                    });
                    return;
                }
                const method = $('#posPaymentMethod').val();
                const total = currentTotal();
                const payload = {
                    _token: @json(csrf_token()),
                    pos_session_id: session.id,
                    cash_bank_account_id: method === 'cash' ? session.cash_account_id : $(
                        '#posPaymentAccount').val(),
                    payment_method: method,
                    paid_amount: method === 'cash' ? $('#posCashReceived').val() : total,
                    discount_rate: $('#posDiscountRate').val() || 0,
                    items: items.map(item => ({
                        product_id: item.id,
                        quantity: item.quantity
                    }))
                };
                Swal.fire({
                    icon: 'question',
                    title: 'Proses penjualan?',
                    text: `Total ${money(total)}`,
                    showCancelButton: true,
                    confirmButtonText: 'Bayar & Simpan',
                    cancelButtonText: 'Periksa Lagi'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const receiptWindow = window.open('about:blank', '_blank');
                    const button = $('#posCheckout').prop('disabled', true);
                    $.ajax({
                            url: @json(route('super.pos.checkout')),
                            method: 'POST',
                            data: payload
                        })
                        .done(response => {
                            if (receiptWindow) receiptWindow.location =
                                `${response.receipt_url}?autoprint=1`;
                            cart.clear();
                            $('#posDiscountRate, #posCashReceived').val(0);
                            refreshCart();
                            $('#posProductSearch').val('').trigger('input').focus();
                            Swal.fire({
                                icon: 'success',
                                title: 'Penjualan Berhasil',
                                html: `<strong>${safe(response.number)}</strong><br>Total ${money(response.total)}${response.change > 0 ? `<br>Kembalian ${money(response.change)}` : ''}${receiptWindow ? '<br><small>Nota siap dicetak.</small>' : '<br><small>Popup cetak diblokir browser. Cetak nota dari Riwayat.</small>'}`,
                                showDenyButton: @json(auth()->user()->can('pos.void')),
                                confirmButtonText: 'Selesai',
                                denyButtonText: 'Void transaksi',
                                denyButtonColor: '#c0392b',
                            }).then(result => {
                                if (result.isDenied) promptVoidSale(response.void_url,
                                    response.number);
                            });
                        })
                        .fail(xhr => {
                            receiptWindow?.close();
                            Swal.fire({
                                icon: 'error',
                                title: 'Penjualan Gagal',
                                text: xhr.responseJSON?.message || Object.values(xhr
                                        .responseJSON?.errors || {}).flat()[0] ||
                                    'Transaksi tidak dapat disimpan.'
                            });
                        })
                        .always(() => button.prop('disabled', false));
                });
            });
            refreshCart();
            if (session) loadProducts();
        });
    </script>
@endpush
