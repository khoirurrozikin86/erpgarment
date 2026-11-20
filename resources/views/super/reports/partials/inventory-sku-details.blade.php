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
                @foreach (['opening_balance', 'enter', 'exit', 'closing_balance'] as $label)
                    <th class="text-end">{{ __('ui.common.'.$label) }}</th>
                @endforeach
                <th>{{ __('ui.common.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($group['details'] as $sku)
                <tr>
                    <td><a href="{{ route('super.stock-card.index', ['product_id' => $sku['id']]) }}">{{ $sku['code'] }}</a></td>
                    <td>{{ $sku['color'] }}</td>
                    <td>{{ $sku['size'] }}</td>
                    @foreach (['opening', 'incoming', 'outgoing', 'closing'] as $balance)
                        <td class="text-end">{{ number_format($sku[$balance], 4, ',', '.') }}</td>
                    @endforeach
                    <td><span class="badge {{ $statusClasses[$sku['status']] }}">{{ __('ui.modules.'.$sku['status']) }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
