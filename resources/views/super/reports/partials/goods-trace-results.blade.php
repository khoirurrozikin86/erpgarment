<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/vendors/core/core.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/nobleui/assets/css/demo1/style.css') }}">
    <style>
        .trace-results h3 {
            font-size: 15px;
            margin-bottom: 4px;
        }

        .trace-results > table {
            font-size: 11px;
            line-height: 1.25;
        }

        .trace-results .table {
            font-size: 11px !important;
            line-height: 1.25;
        }

        .trace-results .table th,
        .trace-results .table td {
            font-size: inherit;
            padding: 4px 6px;
        }

        .trace-results .table th {
            font-size: 10px;
            white-space: normal !important;
        }
    </style>
</head>
<body>
    @php
        $traceParameters = ['product' => $product, 'mo' => $selected?->id, 'receipt_item' => ($selectedReceiptItem ?? null)?->id];
    @endphp

    <main class="trace-results container-fluid bg-white min-vh-100 p-2 p-md-3">
        <div class="d-flex flex-wrap justify-content-end gap-2 mb-2">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route($reportRoute.'trace', $traceParameters) }}">
                {{ __('bc_reports.back') }}
            </a>
            <a class="btn btn-sm btn-outline-primary" href="{{ route($reportRoute.'trace.results', [...$traceParameters, 'export' => 1]) }}">
                {{ __('bc_reports.export_excel') }}
            </a>
        </div>
        @include('super.reports.'.$reportDirection.'.sections')
    </main>
</body>
</html>
