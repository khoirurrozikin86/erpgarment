@extends('super.reports.partials.goods-trace', [
    'reportDirection' => 'outgoing',
    'reportRoute' => 'super.reports.outgoing.',
    'pageTitle' => __('bc_reports.trace_outgoing_goods_2'),
    'formTitle' => __('bc_reports.trace_outgoing_goods_2'),
    'traceColor' => $selected?->line_color,
    'traceSize' => $selected?->line_size,
])
