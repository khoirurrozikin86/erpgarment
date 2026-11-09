<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Pos\Queries\PosSessionQuery;
use App\Domain\Pos\Services\PosSessionService;
use App\Http\Controllers\Controller;
use App\Models\PosSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class PosSessionController extends Controller
{
    public function index(PosSessionQuery $query): View
    {
        return view('super.pos-sessions.index', ['cashAccounts' => $query->activeCashAccounts()]);
    }

    public function dt(Request $request, PosSessionQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
            }],
        ]);

        return DataTables::eloquent($query->sessions($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('account_name', fn (PosSession $session) => $session->cashBankAccount?->code . ' · ' . ($session->cashBankAccount?->name ?? 'Akun dihapus'))
            ->addColumn('expected_cash_current', fn (PosSession $session) => $session->status === 'closed'
                ? (float) $session->expected_cash
                : round((float) $session->opening_cash + (float) $session->cash_in_total - (float) $session->cash_out_total, 2))
            ->addColumn('variance', fn (PosSession $session) => $session->status === 'closed' ? (float) $session->cash_difference : null)
            ->addColumn('status_label', fn (PosSession $session) => $session->status === 'open'
                ? '<span class="badge bg-success">Terbuka</span>'
                : '<span class="badge bg-secondary">Ditutup</span>')
            ->addColumn('actions', function (PosSession $session) {
                if ($session->status !== 'open' || !auth()->user()->can('pos-sessions.close')) return '—';
                $expected = round((float) $session->opening_cash + (float) $session->cash_in_total - (float) $session->cash_out_total, 2);
                return '<button type="button" class="btn btn-sm btn-outline-primary btn-close-pos-session" data-url="' . e(route('super.pos-sessions.close', $session)) . '" data-number="' . e($session->number) . '" data-expected="' . $expected . '" title="Tutup Sesi"><i data-feather="lock"></i></button>';
            })
            ->rawColumns(['status_label', 'actions'])->toJson();
    }

    public function open(Request $request, PosSessionService $service): JsonResponse
    {
        $data = $request->validate([
            'cash_bank_account_id' => ['required', 'integer', 'exists:cash_bank_accounts,id'],
            'opening_cash' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $session = $service->open($data, (int) $request->user()->id);
        return response()->json(['message' => "Sesi {$session->number} berhasil dibuka."], 201);
    }

    public function close(Request $request, PosSession $posSession, PosSessionService $service): JsonResponse
    {
        $data = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $session = $service->close($posSession, $data, (int) $request->user()->id);
        return response()->json([
            'message' => "Sesi {$session->number} berhasil ditutup.",
            'expected_cash' => (float) $session->expected_cash,
            'counted_cash' => (float) $session->counted_cash,
            'cash_difference' => (float) $session->cash_difference,
        ]);
    }
}
