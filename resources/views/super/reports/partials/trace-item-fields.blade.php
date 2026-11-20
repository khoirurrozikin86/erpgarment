@php
    $headings = \App\Domain\Purchasing\Queries\GoodsTraceItemColumns::headings();
    $values = \App\Domain\Purchasing\Queries\GoodsTraceItemColumns::values(
        $product,
        $traceColor,
        $traceSize,
    );
    $headings[] = __('bc_reports.unit');
    $values[] = $product->unit?->symbol ?: $product->unit?->name;
@endphp

@if($horizontal ?? false)
    <div class="row g-3 g-xl-4">
        @foreach(array_chunk($headings, 4, true) as $column)
            <div class="col-xl-6">
                @foreach($column as $index => $heading)
                    <div class="row g-2 align-items-center mb-2">
                        <label class="col-md-4 col-form-label col-form-label-sm" for="trace-item-{{ $index }}">{{ $heading }}</label>
                        <div class="col-md-8">
                            <input id="trace-item-{{ $index }}" class="form-control form-control-sm"
                                value="{{ filled($values[$index]) ? $values[$index] : '—' }}" readonly>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
@else
@foreach($headings as $index => $heading)
    <div class="{{ $index === 2 ? 'col-md-6' : 'col-md-3' }}">
        <label class="form-label" for="trace-item-{{ $index }}">{{ $heading }}</label>
        <input id="trace-item-{{ $index }}" class="form-control" value="{{ filled($values[$index]) ? $values[$index] : '—' }}" readonly>
    </div>
@endforeach
@endif
