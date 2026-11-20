
@foreach($contracts as $contract)<tr><td>{{ $contracts->firstItem()+$loop->index }}</td><td>{{ $contract->subcontractor?->name ?? '—' }}</td><td>{{ $contract->contract_number }}</td><td>{{ $contract->bc_decree_number ?: '—' }}</td><td>{{ $contract->description ?: '—' }}</td><td>{{ $contract->starts_on?->format('d-m-Y') ?? '—' }}</td><td>{{ $contract->ends_on?->format('d-m-Y') ?? '—' }}</td><td class="text-end">{{ $contract->quota === null ? '—' : \App\Support\DisplayNumber::format($contract->quota, 4) }}</td></tr>
@endforeach

