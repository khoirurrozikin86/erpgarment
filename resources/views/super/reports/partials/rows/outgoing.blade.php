@foreach($items as $item)
    <tr>
        <td>{{ $items->firstItem() + $loop->index }}</td>
        <td>—</td>
        <td>—</td>
        <td>—</td>
        <td>{{ $item->number }}</td>
        <td>{{ \Carbon\Carbon::parse($item->document_date)->format('d-m-Y') }}</td>
        <td>{{ $item->recipient ?? '—' }}</td>
        <td>—</td>
        @foreach(['parent_code', 'sku_code'] as $column)
            <td>
                @if($item->product_id && $column === ($item->sku_code ? 'sku_code' : 'parent_code'))
                    <a class="js-trace-product" href="{{ route('super.reports.outgoing.trace', $item->product_id) }}"
                        data-code="{{ $item->item_code ?? $item->{$column} }}" data-name="{{ $item->item_name }}" data-unit="{{ $item->unit }}">
                        {{ $item->{$column} ?? '—' }}
                    </a>
                @else
                    {{ $item->{$column} ?? '—' }}
                @endif
            </td>
        @endforeach
        <td class="item-name">{{ $item->item_name }}</td><td>{{ $item->category_name ?? '—' }}</td>
        <td>{{ $item->color ?? '—' }}</td>
        <td>{{ $item->size ?? '—' }}</td>
        <td>{{ $item->unit }}</td>
        <td class="text-end">{{ number_format($item->quantity, 4, ',', '.') }}</td>
        <td class="text-end">{{ $item->unit_price === null ? '—' : number_format($item->unit_price, 4, ',', '.') }}</td>
        <td class="text-end">{{ $item->total_amount === null ? '—' : number_format($item->total_amount, 4, ',', '.') }}</td>
    </tr>
@endforeach
