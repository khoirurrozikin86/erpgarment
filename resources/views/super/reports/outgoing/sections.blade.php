<table><tr><td colspan="12"><h3>LACAK PENGELUARAN BARANG</h3></td></tr><tr><td>No MO</td><td colspan="11">{{ $selected?->number ?? 'ALL' }}</td></tr><tr><td>Qty keluar</td><td colspan="11">{{ number_format($quantity, 4, ',', '.') }}</td></tr><tr><td>Barang Jadi</td><td colspan="11">{{ $product->code }} | {{ $product->name }}</td></tr><tr><td>Satuan</td><td colspan="11">{{ $product->unit?->symbol ?: $product->unit?->name }}</td></tr></table>
@foreach($sections as $section)
<div class="mt-4 mb-2" style="color:navy">{{ $section['title'] }}</div>
<div class="table-responsive"><table class="table table-bordered table-striped align-middle" style="font-size:12px"><thead><tr><th>No</th>@foreach($section['headers'] as $header)<th style="background:#f8f8f8;white-space:nowrap">{{ $header }}</th>@endforeach</tr></thead><tbody>
@forelse($section['rows'] as $row)<tr><td>{{ $loop->iteration }}</td>@foreach($row as $value)<td>{{ $value === null || $value === '' ? '—' : $value }}</td>@endforeach</tr>
@empty<tr><td colspan="{{ count($section['headers']) + 1 }}">Belum ada transaksi terkait yang dapat ditelusuri.</td></tr>@endforelse
</tbody></table></div>
@if(isset($section['note']))<p class="small text-muted">{{ $section['note'] }}</p>@endif
@endforeach
