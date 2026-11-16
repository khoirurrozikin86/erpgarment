@foreach($items as $item)
                @php($values = \App\Domain\Inventory\Queries\CapitalGoodsMutationReportQuery::values($item))
                <tr><td>{{ $items->firstItem() + $loop->index }}</td><td>{{ $item->code }}</td><td class="item-name">{{ $item->name }}</td><td>{{ $item->unit?->symbol ?: $item->unit?->name }}</td><td class="text-end" title="{{ __('bc_reports.item_quantity_definition_awaits_confirmation') }}">—</td>
                @foreach(['opening', 'incoming', 'outgoing', 'adjustment', 'closing', 'counted', 'difference'] as $key)<td class="text-end">{{ $values[$key] === null ? '—' : number_format($values[$key], 2, ',', '.') }}</td>@endforeach
                <td>{{ $values['notes'] ?? '—' }}</td></tr>
            @endforeach
