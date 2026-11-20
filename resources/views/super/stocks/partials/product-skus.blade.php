<div class="px-3 py-2">
    <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
            <tr>
                <th>{{ __('variants.stock_sku') }}</th>
                <th>{{ __('ui.workflow.color') }}</th>
                <th>{{ __('ui.workflow.size') }}</th>
                <th>{{ __('ui.common.unit') }}</th>
                <th>{{ __('ui.common.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                @php($unit = $product->unit?->symbol ?: $product->unit?->name)
                <tr>
                    <td>{{ $product->code }}</td>
                    <td>{{ $product->color ?? '—' }}</td>
                    <td>{{ $product->size ?? '—' }}</td>
                    <td>{{ $unit ?? '—' }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-select-stock-sku"
                            data-id="{{ $product->id }}" data-parent-code="{{ $parent->code }}" data-code="{{ $product->code }}"
                            data-name="{{ $product->name }}" data-color="{{ $product->color }}" data-size="{{ $product->size }}" data-unit="{{ $unit }}">
                            {{ __('ui.workflow.select') }}
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
