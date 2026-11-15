<table><tr><td colspan="12"><h3>{{ __('bc_reports.trace_outgoing_goods') }}</h3></td></tr><tr><td>{{ __('bc_reports.mo_no') }}</td><td colspan="11">{{ $selected?->number ?? __('bc_reports.all') }}</td></tr><tr><td>{{ __('bc_reports.outgoing_quantity_2') }}</td><td colspan="11">{{ number_format($quantity, 4, ',', '.') }}</td></tr><tr><td>{{ __('bc_reports.finished_goods') }}</td><td colspan="11">{{ $product->code }} | {{ $product->name }}</td></tr><tr><td>{{ __('bc_reports.unit') }}</td><td colspan="11">{{ $product->unit?->symbol ?: $product->unit?->name }}</td></tr></table>
@foreach($sections as $section)
<div class="mt-4 mb-2" style="color:navy">{{ $section['title'] }}</div>
<div class="table-responsive"><table class="table table-bordered table-striped align-middle" style="font-size:12px"><thead><tr><th>{{ __('bc_reports.no') }}</th>@foreach($section['headers'] as $header)<th style="background:#f8f8f8;white-space:nowrap">{{ $header }}</th>@endforeach</tr></thead><tbody>
@forelse($section['rows'] as $row)<tr><td>{{ $loop->iteration }}</td>@foreach($row as $value)<td>{{ $value === null || $value === '' ? '—' : $value }}</td>@endforeach</tr>
@empty<tr><td colspan="{{ count($section['headers']) + 1 }}">{{ __('bc_reports.no_related_transactions_available_to_trace') }}</td></tr>@endforelse
</tbody></table></div>
@if(isset($section['note']))<p class="small text-muted">{{ $section['note'] }}</p>@endif
@endforeach
