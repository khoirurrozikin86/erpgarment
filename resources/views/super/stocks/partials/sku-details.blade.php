@php
    $statusClasses = ['safe_stock' => 'bg-success', 'low_stock' => 'bg-warning text-dark', 'out_of_stock' => 'bg-danger'];
@endphp
<div class="px-3 py-2">
    <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
            <tr>
                <th>{{ __('variants.stock_sku') }}</th>
                <th>{{ __('ui.workflow.color') }}</th>
                <th>{{ __('ui.workflow.size') }}</th>
                <th>{{ __('ui.common.unit') }}</th>
                <th class="text-end">{{ __('variants.stock_sku_balance') }}</th>
                <th class="text-end">{{ __('ui.workflow.reorder_point') }}</th>
                <th>{{ __('ui.common.status') }}</th>
                <th>{{ __('ui.workflow.lot_roll') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($group['details'] as $sku)
                <tr>
                    <td>
                        @can('stock-card.view')
                            <a href="{{ route('super.stock-card.index', ['product_id' => $sku['id']]) }}">{{ $sku['code'] }}</a>
                        @else
                            {{ $sku['code'] }}
                        @endcan
                        @if ($sku['code'] === $group['code'] && ($sku['color'] !== '—' || $sku['size'] !== '—'))
                            <div class="small text-muted">{{ __('variants.stock_historical_variants') }}</div>
                        @endif
                    </td>
                    <td>{{ $sku['color'] }}</td>
                    <td>{{ $sku['size'] }}</td>
                    <td>{{ $group['unit'] ?? '—' }}</td>
                    <td class="text-end">{{ number_format($sku['quantity'], 4, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($sku['threshold'], 4, ',', '.') }}</td>
                    <td><span class="badge {{ $statusClasses[$sku['status']] }}">{{ __('ui.modules.'.$sku['status']) }}</span></td>
                    <td><button type="button" class="btn btn-outline-primary btn-sm view-item-lots" data-id="{{ $sku['id'] }}" data-name="{{ $sku['name'] }}">{{ __('ui.workflow.view_lots') }}</button></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
