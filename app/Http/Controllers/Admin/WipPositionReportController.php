<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Queries\WipPositionReportQuery;
use App\Exports\WipPositionReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class WipPositionReportController extends Controller
{
    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'], 'to_date' => ['nullable', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $filters['from_date'] ??= now()->startOfMonth()->toDateString();
        $filters['to_date'] ??= now()->toDateString();
        if ($filters['from_date'] > $filters['to_date']) throw ValidationException::withMessages(['to_date' => __('bc_reports.the_end_date_must_be_on_or_after_the_start_date')]);
        return $filters;
    }

    public function index(Request $request, WipPositionReportQuery $query, CompanyContext $company)
    {
        $filters = $this->filters(new Request($request->except(['search'])));
        if ($request->ajax()) {
            return app(\App\Domain\Customs\Queries\BcReportDataTable::class)->response(
                $request, $filters, 'wip-position', fn (array $filters) => $query->builder($company->id(), $filters)
            );
        }
        return view('super.reports.wip-position', compact('filters'));
    }

    public function export(Request $request, CompanyContext $company)
    {
        return Excel::download(new WipPositionReportExport($company->id(), $this->filters($request)), 'laporan-posisi-wip.xlsx');
    }
}
