@extends('layouts.admin')

@section('title', 'Laporan Penjualan POS')

@section('breadcrumb')
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Report</a></li>
            <li class="breadcrumb-item active" aria-current="page">Penjualan POS</li>
        </ol>
    </nav>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="page-title mb-1">Laporan Penjualan POS</h4><span class="text-muted">Transaksi POS, void, pembayaran,
                diskon, pajak, dan rincian barang.</span>
        </div>
    </div>

    <form method="GET" class="card mb-3">
        <div class="card-body py-3">
            <div class="row align-items-end g-2">
                <div class="col-sm-6 col-md-2"><label for="from_date" class="form-label mb-1">Dari Tanggal</label><input
                        id="from_date" name="from_date" type="date" class="form-control form-control-sm"
                        value="{{ $filters['from_date'] }}" required></div>
                <div class="col-sm-6 col-md-2"><label for="to_date" class="form-label mb-1">Sampai Tanggal</label><input
                        id="to_date" name="to_date" type="date" class="form-control form-control-sm"
                        value="{{ $filters['to_date'] }}" required></div>
                <div class="col-sm-6 col-md-2"><label for="cashier_id" class="form-label mb-1">Kasir</label><select
                        id="cashier_id" name="cashier_id" class="form-select form-select-sm">
                        <option value="">Semua kasir</option>
                        @foreach ($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" @selected((string) ($filters['cashier_id'] ?? '') === (string) $cashier->id)>{{ $cashier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2"><label for="pos_session_id" class="form-label mb-1">Sesi POS</label><select
                        id="pos_session_id" name="pos_session_id" class="form-select form-select-sm">
                        <option value="">Semua sesi</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}" @selected((string) ($filters['pos_session_id'] ?? '') === (string) $session->id)>{{ $session->number }} ·
                                {{ $session->opened_at?->format('d/m/Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2"><label for="payment_method" class="form-label mb-1">Pembayaran</label><select
                        id="payment_method" name="payment_method" class="form-select form-select-sm">
                        <option value="">Semua metode</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['payment_method'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select></div>
                <div class="col-sm-6 col-md-2 d-flex gap-2"><button class="btn btn-sm btn-primary" type="submit"><i
                            data-feather="filter" class="icon-sm me-1"></i>Tampilkan</button><button
                        class="btn btn-sm btn-outline-success" type="submit"
                        formaction="{{ route('super.reports.pos-sales.export') }}"><i data-feather="download"
                            class="icon-sm me-1"></i>CSV</button></div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Transaksi Selesai</div>
                    <div class="fs-5 fw-semibold">{{ number_format($summary['transaction_count'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Penjualan</div>
                    <div class="fs-5 fw-semibold">Rp {{ number_format($summary['total'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Diskon</div>
                    <div class="fs-5 fw-semibold text-danger">Rp {{ number_format($summary['discount'], 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Pajak</div>
                    <div class="fs-5 fw-semibold">Rp {{ number_format($summary['tax'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Uang Diterima</div>
                    <div class="fs-5 fw-semibold text-success">Rp {{ number_format($summary['paid'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Kembalian</div>
                    <div class="fs-5 fw-semibold">Rp {{ number_format($summary['change'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Transaksi Void</div>
                    <div class="fs-5 fw-semibold text-danger">{{ number_format($summary['voided_count'], 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Nilai Transaksi Void</div>
                    <div class="fs-5 fw-semibold text-danger">Rp
                        {{ number_format($summary['voided_total'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nomor Transaksi</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Sesi</th>
                            <th>Customer</th>
                            <th>Barang</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end">Pajak</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Uang Diterima</th>
                            <th class="text-end">Kembalian</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr class="{{ $sale->status === 'voided' ? 'text-muted' : '' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $sale->number }}</td>
                                <td class="text-nowrap">{{ $sale->sold_at?->format('d/m/Y H:i') }}</td>
                                <td>{{ $sale->cashier?->name ?? 'User dihapus' }}</td>
                                <td>{{ $sale->session?->number ?? '—' }}</td>
                                <td>{{ $sale->customer?->name ?? 'Walk-in Customer' }}</td>
                                <td>
                                    <details>
                                        <summary>{{ $sale->items_count }} item</summary>
                                        <div class="table-responsive mt-2">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Barang</th>
                                                        <th class="text-end">Qty</th>
                                                        <th class="text-end">Harga</th>
                                                        <th class="text-end">Diskon</th>
                                                        <th class="text-end">Pajak</th>
                                                        <th class="text-end">Total Baris</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($sale->items as $item)
                                                        <tr>
                                                            <td>{{ $item->product?->code ?? '—' }} ·
                                                                {{ $item->product?->name ?? 'Barang dihapus' }}</td>
                                                            <td class="text-end">
                                                                {{ number_format((float) $item->quantity, 4, ',', '.') }}
                                                                {{ $item->product?->unit?->symbol ?: $item->product?->unit?->name }}
                                                            </td>
                                                            <td class="text-end">Rp
                                                                {{ number_format((float) $item->unit_price, 2, ',', '.') }}
                                                            </td>
                                                            <td class="text-end">Rp
                                                                {{ number_format((float) $item->discount_amount, 2, ',', '.') }}
                                                            </td>
                                                            <td class="text-end">Rp
                                                                {{ number_format((float) $item->tax_amount, 2, ',', '.') }}
                                                            </td>
                                                            <td class="text-end">Rp
                                                                {{ number_format((float) $item->line_total, 2, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </details>
                                </td>
                                <td class="text-end text-nowrap">Rp
                                    {{ number_format((float) $sale->subtotal, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">Rp
                                    {{ number_format((float) $sale->discount_amount, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">Rp
                                    {{ number_format((float) $sale->tax_amount, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap fw-semibold">Rp
                                    {{ number_format((float) $sale->total_amount, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">Rp
                                    {{ number_format((float) $sale->paid_amount, 2, ',', '.') }}</td>
                                <td class="text-end text-nowrap">Rp
                                    {{ number_format((float) $sale->change_amount, 2, ',', '.') }}</td>
                                <td>{{ $paymentMethods[$sale->payment_method] ?? $sale->payment_method }}</td>
                                <td>
                                    @if ($sale->status === 'voided')
                                        <span class="badge bg-danger">VOID</span>
                                        <div class="small mt-1">{{ $sale->void_reason }}</div>
                                        <div class="small">{{ $sale->voided_at?->format('d/m/Y H:i') }} ·
                                            {{ $sale->voider?->name ?? 'User dihapus' }}</div>
                                    @else
                                        <span class="badge bg-success">Selesai</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (
                                        $sale->status === 'completed' &&
                                            auth()->user()->can('pos.void') &&
                                            $sale->session?->status === 'open' &&
                                            (int) $sale->cashier_id === (int) auth()->id())
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-void-pos-sale"
                                            data-url="{{ route('super.pos.void', $sale) }}"
                                            data-number="{{ $sale->number }}" title="Void transaksi"><i
                                                data-feather="x-circle" class="icon-sm"></i></button>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center text-muted py-4">Tidak ada transaksi POS pada filter
                                    ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $(document).on('click', '.btn-void-pos-sale', function() {
                const button = $(this);
                Swal.fire({
                    icon: 'warning',
                    title: `Void ${button.data('number')}?`,
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
                        url: button.data('url'),
                        method: 'PUT',
                        data: {
                            _token: @json(csrf_token()),
                            reason
                        },
                    }).catch(xhr => Swal.showValidationMessage(xhr.responseJSON?.message ||
                        Object.values(xhr.responseJSON?.errors || {}).flat()[0] ||
                        'Void gagal diproses.')),
                }).then(result => {
                    if (!result.isConfirmed || !result.value?.message) return;
                    Swal.fire({
                        icon: 'success',
                        title: 'Transaksi di-void',
                        text: result.value.message
                    }).then(() => window.location.reload());
                });
            });
        });
    </script>
@endpush
