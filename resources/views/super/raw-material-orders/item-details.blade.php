@if($items->isEmpty())
    —
@else
    @php
        $format = fn ($quantity) => rtrim(rtrim(number_format($quantity, 4, ',', '.'), '0'), ',');
    @endphp
    <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                @foreach([
                    'variants.stock_parent_code',
                    'variants.stock_sku',
                    'ui.raw_material_order.item_name',
                    'ui.common.category',
                    'ui.workflow.color',
                    'ui.workflow.size',
                    'ui.common.unit',
                    'ui.raw_material_order.quantity',
                    'ui.raw_material_order.sent_quantity',
                    'ui.raw_material_order.ship_status',
                ] as $label)
                    <th scope="col" class="text-nowrap">{{ __($label) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                @php
                    $issued = round(collect($item['sources'])->sum('issued_quantity'), 4);
                    [$status, $badge] = match (true) {
                        $issued <= 0 => ['not_sent', 'bg-secondary'],
                        $issued < $item['quantity'] => ['partially_sent', 'bg-warning text-dark'],
                        default => ['fully_sent', 'bg-success'],
                    };
                @endphp
                <tr>
                    <td class="text-nowrap">{{ $item['parent_code'] ?? '—' }}</td>
                    <td class="text-nowrap">{{ $item['sku_code'] ?? '—' }}</td>
                    <td>{{ $item['name'] ?? '—' }}</td>
                    <td>{{ collect($item['categories'])->map(fn ($type) => __('variants.bom_type_'.$type))->implode(', ') ?: '—' }}</td>
                    <td>{{ $item['color'] ?: '—' }}</td>
                    <td>{{ $item['size'] ?: '—' }}</td>
                    <td>{{ $item['unit'] ?? '—' }}</td>
                    <td class="text-end">{{ $format($item['quantity']) }}</td>
                    <td class="text-end">{{ $format($issued) }}</td>
                    <td><span class="badge {{ $badge }}">{{ __('ui.raw_material_order.'.$status) }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
