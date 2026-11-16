<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Queries\MaterialMutationReportQuery;
use App\Exports\MaterialMutationReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class MaterialMutationReportController extends Controller
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

    public function index(Request $request, MaterialMutationReportQuery $query)
    {
        $filters = $this->filters(new Request($request->except(['search'])));
        if ($request->ajax()) {
            return app(\App\Domain\Customs\Queries\BcReportDataTable::class)->response(
                $request, $filters, 'material-mutation', fn (array $filters) => $query->builder($filters)
            );
        }
        return view('super.reports.material-mutation', compact('filters'));
    }

    public function export(Request $request)
    {
        return Excel::download(new MaterialMutationReportExport($this->filters($request)), 'laporan-mutasi-bahan.xlsx');
    }
}
