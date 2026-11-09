<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Accounting\Queries\ProfitLossQuery;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class ProfitLossController extends Controller
{
    public function index(Request $request, ProfitLossQuery $query): View
    {
        $defaultFrom = now()->startOfMonth()->toDateString();
        $defaultTo = now()->toDateString();
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $fromDate = $filters['from_date'] ?? $defaultFrom;
        $toDate = $filters['to_date'] ?? $defaultTo;
        if ($fromDate > $toDate) {
            throw ValidationException::withMessages(['to_date' => 'Tanggal akhir harus sama atau setelah tanggal awal.']);
        }

        $rows = $query->rows($fromDate, $toDate);
        $revenue = round($rows->where('account_type', 'revenue')->sum('amount'), 2);
        $expense = round($rows->where('account_type', 'expense')->sum('amount'), 2);

        return view('super.profit-loss.index', compact('rows', 'revenue', 'expense', 'fromDate', 'toDate'));
    }
}
