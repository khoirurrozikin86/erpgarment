@foreach($items as $item)
                @php($product = $item->outputProduct)
                <tr><td>{{ $item->buyer?->name ?? '—' }}</td><td>{{ $item->number }}</td><td>{{ $item->buyer_po_number ?: '—' }}</td><td>{{ $item->model_style ?: '—' }}</td><td>{{ $product?->parentProduct?->code ?? $product?->code ?? '—' }}</td><td>{{ $product?->parent_product_id ? $product->code : '—' }}</td><td class="item-name">{{ $product?->name ?? '—' }}</td><td>{{ $item->line_color ?: '—' }}</td><td>{{ $item->line_size ?: '—' }}</td>
                @foreach(\App\Domain\Manufacturing\Queries\DailyProductionStatusReportQuery::values($item) as $value)<td class="text-end">{{ \App\Support\DisplayNumber::format($value, 4) }}</td>@endforeach</tr>
            @endforeach
