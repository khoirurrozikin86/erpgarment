<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Accounting\Queries\CashBankReportQuery;
use App\Exports\CashBankReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CashBankReportController extends Controller
{
    public function index(Request $request, CashBankReportQuery $query): View
    {
        [$fromDate, $toDate] = $this->period($request);
        $accounts = $query->accounts($fromDate, $toDate)->get()->map(function ($account) {
            $account->report_opening = round((float) $account->opening_balance + (float) $account->prior_in - (float) $account->prior_out, 2);
            $account->report_incoming = round((float) $account->period_in, 2);
            $account->report_outgoing = round((float) $account->period_out, 2);
            $account->report_closing = round($account->report_opening + $account->report_incoming - $account->report_outgoing, 2);
            return $account;
        });
        $summary = [
            'accounts' => $accounts->count(),
            'opening' => round($accounts->sum('report_opening'), 2),
            'incoming' => round($accounts->sum('report_incoming'), 2),
            'outgoing' => round($accounts->sum('report_outgoing'), 2),
            'closing' => round($accounts->sum('report_closing'), 2),
        ];

        return view('super.reports.cash-bank', compact('accounts', 'summary', 'fromDate', 'toDate'));
    }

    public function export(Request $request)
    {
        [$fromDate, $toDate] = $this->period($request);
        return Excel::download(new CashBankReportExport($fromDate, $toDate), "laporan-kas-bank-{$fromDate}-{$toDate}.xlsx");
    }

    private function period(Request $request): array
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();
        if ($fromDate > $toDate) {
            throw ValidationException::withMessages(['to_date' => 'Tanggal akhir harus sama atau setelah tanggal awal.']);
        }
        return [$fromDate, $toDate];
    }
}
