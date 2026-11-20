@extends('super.reports.partials.goods-trace', [
    'reportDirection' => 'incoming',
    'reportRoute' => 'super.reports.incoming.',
    'pageTitle' => __('bc_reports.trace_incoming_materials_2'),
    'formTitle' => __('bc_reports.trace_incoming_goods'),
    'traceColor' => $selectedReceiptItem?->received_color,
    'traceSize' => $selectedReceiptItem?->received_size,
])
