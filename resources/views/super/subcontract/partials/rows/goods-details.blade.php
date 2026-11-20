
@foreach($details as $detail)<tr><td>{{ $details->firstItem()+$loop->index }}</td><td>{{ $detail->style ?: '—' }}</td>@foreach(\App\Domain\Subcontract\Queries\SubcontractProductColumns::values($detail->product) as $value)<td>{{ filled($value) ? $value : '—' }}</td>@endforeach<td>{{ $detail->product?->unit?->symbol ?: $detail->product?->unit?->name }}</td><td>—</td><td class="text-end">{{ \App\Support\DisplayNumber::format($detail->realized_quantity, 4) }}</td><td>—</td></tr>
@endforeach

