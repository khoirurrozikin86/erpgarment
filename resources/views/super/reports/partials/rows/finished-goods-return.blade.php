@foreach($items as $item)
                @php($product = $item->product)
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>—</td><td>{{ $item->created_at?->format('Y-m-d') }}</td><td>{{ $product?->parentProduct?->code ?? $product?->code ?? '—' }}</td><td>{{ $product?->parent_product_id ? $product->code : '—' }}</td><td class="item-name">{{ $product?->name ?? '—' }}</td><td>{{ ($product?->parent_product_id ? $product->color : null) ?: '—' }}</td><td>{{ ($product?->parent_product_id ? $product->size : null) ?: '—' }}</td><td>{{ $product?->unit?->symbol ?: $product?->unit?->name }}</td><td class="text-end">{{ \App\Support\DisplayNumber::format($item->quantity, 4) }}</td></tr>
            @endforeach
