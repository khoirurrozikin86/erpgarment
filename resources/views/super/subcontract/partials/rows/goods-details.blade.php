
@foreach($details as $detail)<tr><td>{{ $details->firstItem()+$loop->index }}</td><td>{{ $detail->style ?: '—' }}</td><td>{{ $detail->product?->code ?? '—' }}</td><td>{{ $detail->product?->name ?? '—' }}</td><td>{{ $detail->product?->unit?->symbol ?: $detail->product?->unit?->name }}</td><td>—</td><td class="text-end">{{ number_format($detail->realized_quantity,4,',','.') }}</td><td>—</td></tr>
@endforeach

