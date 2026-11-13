<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Actions\SaveLostItemAction;
use App\Domain\Inventory\Queries\LostItemTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\LostItemsExport;
use App\Http\Controllers\Controller;
use App\Models\{LostItemLine, Product};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class LostItemController extends Controller
{
    public function index(): View { return view('super.lost-items.index'); }

    public function export()
    {
        return Excel::download(new LostItemsExport(), 'lost-items.xlsx');
    }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse
    {
        return response()->json(['number' => $numbers->preview('lost_item')]);
    }

    public function dt(LostItemTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('number', fn (LostItemLine $line) => $line->document->number)
            ->addColumn('document_date', fn (LostItemLine $line) => $line->document->document_date?->format('Y-m-d'))
            ->addColumn('code', fn (LostItemLine $line) => $line->product->code)
            ->addColumn('name', fn (LostItemLine $line) => $line->product->name)
            ->addColumn('unit', fn (LostItemLine $line) => $line->product->unit?->symbol ?: $line->product->unit?->name ?: '—')
            ->addColumn('actions', function (LostItemLine $line) {
                $items = '';
                if (auth()->user()->can('lost-items.update')) {
                    $items .= '<li><button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-edit-lost-item" data-id="'.(int)$line->id.'" data-number="'.e($line->document->number).'" data-date="'.$line->document->document_date?->format('Y-m-d').'" data-product="'.(int)$line->product_id.'" data-code="'.e($line->product->code).'" data-name="'.e($line->product->name).'" data-unit="'.e($line->product->unit?->symbol ?: $line->product->unit?->name ?: '').'" data-quantity="'.e($line->quantity).'" data-description="'.e($line->description).'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i>'.e(__('ui.common.edit')).'</button></li>';
                }
                if (auth()->user()->can('lost-items.delete')) {
                    $items .= '<li><button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-lost-item" data-url="'.e(route('super.lost-items.destroy', $line)).'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i>'.e(__('ui.common.delete')).'</button></li>';
                }
                if ($items === '') return '';
                return '<div class="dropdown d-inline-block"><button class="btn btn-sm bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="'.e(__('ui.common.actions')).'"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm">'.$items.'</ul></div>';
            })
            ->rawColumns(['actions'])->toJson();
    }

    public function productsDt(LostItemTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn (Product $product) => '<button type="button" class="btn btn-sm btn-primary btn-select-lost-product" data-id="'.(int)$product->id.'" data-code="'.e($product->code).'" data-name="'.e($product->name).'" data-unit="'.e($product->unit?->symbol ?: $product->unit?->name ?: '').'"><i data-feather="check"></i></button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function store(Request $request, SaveLostItemAction $action): JsonResponse
    {
        $line = $action->create($this->validated($request), (int)$request->user()->id);
        return response()->json(['message' => __('ui.lost.saved'), 'id' => $line->id, 'number' => $line->document->number], 201);
    }

    public function update(Request $request, LostItemLine $lostItemLine, SaveLostItemAction $action): JsonResponse
    {
        $action->update($lostItemLine, $this->validated($request), (int)$request->user()->id);
        return response()->json(['message' => __('ui.lost.updated')]);
    }

    public function destroy(Request $request, LostItemLine $lostItemLine, SaveLostItemAction $action): JsonResponse
    {
        $action->delete($lostItemLine, (int) $request->user()->id);
        return response()->json(['message' => __('ui.lost.deleted')]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'document_date' => ['required', 'date'],
            'product_id' => ['required', 'integer', 'exists:products,id'], 'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
