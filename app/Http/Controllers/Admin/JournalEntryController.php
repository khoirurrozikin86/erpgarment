<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Accounting\Queries\JournalEntryQuery;
use App\Domain\Accounting\Services\JournalEntryService;
use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class JournalEntryController extends Controller
{
    public function index(): View { return view('super.journals.index'); }

    public function create(JournalEntryQuery $query): View
    {
        return view('super.journals.create', ['accounts' => $query->accounts()]);
    }

    public function dt(Request $request, JournalEntryQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
            }],
        ]);
        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('status_label', fn (JournalEntry $entry) => $entry->status === 'posted' ? '<span class="badge bg-success">Posted</span>' : '<span class="badge bg-warning text-dark">Draft</span>')
            ->addColumn('actions', function (JournalEntry $entry) {
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-journal" data-url="' . e(route('super.journals.show', $entry)) . '" title="Detail"><i data-feather="eye"></i></button>';
                if ($entry->status === 'draft' && auth()->user()->can('journal.post')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-success btn-post-journal" data-url="' . e(route('super.journals.post', $entry)) . '" data-number="' . e($entry->number) . '" title="Posting"><i data-feather="check"></i></button>';
                }
                if ($entry->status === 'draft' && auth()->user()->can('journal.delete')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-danger btn-delete-journal" data-url="' . e(route('super.journals.destroy', $entry)) . '" data-number="' . e($entry->number) . '" title="Hapus draft"><i data-feather="trash-2"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'actions'])->toJson();
    }

    public function store(Request $request, JournalEntryService $service): JsonResponse
    {
        $data = $request->validate([
            'journal_date' => ['required', 'date_format:Y-m-d'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.chart_of_account_id' => ['required', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'lines.*.credit' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ]);
        $entry = $service->create($data, (int) $request->user()->id);
        return response()->json(['message' => "Draft jurnal {$entry->number} berhasil disimpan.", 'url' => route('super.journals.index')], 201);
    }

    public function show(JournalEntry $journalEntry, JournalEntryQuery $query): JsonResponse
    {
        $entry = $query->find($journalEntry->id);
        return response()->json([
            'number' => $entry->number,
            'journal_date' => $entry->journal_date?->format('d/m/Y'),
            'reference_number' => $entry->reference_number ?: '—',
            'description' => $entry->description,
            'status' => $entry->status,
            'creator' => $entry->creator?->name ?? '—',
            'poster' => $entry->poster?->name ?? '—',
            'posted_at' => $entry->posted_at?->format('d/m/Y H:i') ?? '—',
            'total_debit' => (float) $entry->total_debit,
            'total_credit' => (float) $entry->total_credit,
            'lines' => $entry->lines->map(fn ($line) => [
                'code' => $line->account?->code ?? '—', 'account' => $line->account?->name ?? 'Akun dihapus',
                'description' => $line->description ?: '—', 'debit' => (float) $line->debit, 'credit' => (float) $line->credit,
            ]),
        ]);
    }

    public function post(Request $request, JournalEntry $journalEntry, JournalEntryService $service): JsonResponse
    {
        $entry = $service->post($journalEntry, (int) $request->user()->id);
        return response()->json(['message' => "Jurnal {$entry->number} berhasil diposting."]);
    }

    public function destroy(JournalEntry $journalEntry, JournalEntryService $service): JsonResponse
    {
        $service->deleteDraft($journalEntry);
        return response()->json(['message' => "Draft jurnal {$journalEntry->number} berhasil dihapus."]);
    }
}
