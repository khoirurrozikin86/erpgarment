@foreach($items as $item)
                <tr><td>{{ $item->buyer?->name ?? '—' }}</td><td>{{ $item->number }}</td><td>{{ $item->buyer_po_number ?: '—' }}</td><td>{{ $item->outputProduct?->style_code ?: '—' }}</td><td>{{ $item->outputProduct?->code ?? '—' }}</td><td class="item-name">{{ $item->outputProduct?->name ?? '—' }}</td><td>{{ $item->outputProduct?->color ?: '—' }}</td><td>{{ $item->outputProduct?->size ?: '—' }}</td><td class="text-end">{{ rtrim(rtrim(number_format($item->planned_quantity, 4, ',', '.'), '0'), ',') }}</td>
                @foreach(\App\Domain\Manufacturing\Queries\DailyProductionStatusReportQuery::values($item) as $value)<td class="text-end">{{ rtrim(rtrim(number_format($value, 4, ',', '.'), '0'), ',') }}</td>@endforeach</tr>
            @endforeach
