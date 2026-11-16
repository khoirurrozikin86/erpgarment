@foreach($items as $item)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->purchaseReturn?->number ?? '—' }}</td><td>{{ $item->purchaseReturn?->returned_at?->format('Y-m-d') }}</td><td>{{ $item->product?->code ?? '—' }}</td><td class="item-name">{{ $item->product?->name ?? '—' }}</td><td>{{ $item->product?->unit?->symbol ?: $item->product?->unit?->name }}</td><td class="text-end">{{ number_format($item->quantity, 4, ',', '.') }}</td></tr>
            @endforeach
