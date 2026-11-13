<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Inventory\Actions\SaveDamagedItemAction;
use App\Domain\Inventory\Queries\DamagedItemTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Http\Controllers\Controller;
use App\Models\{DamagedItemLine, Product};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class DamagedItemController extends Controller
{
    public function index(): View { return view('super.damaged-items.index'); }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse
    {
        return response()->json(['number' => $numbers->preview('damaged_item')]);
    }

    public function dt(DamagedItemTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('number', fn (DamagedItemLine $line) => $line->document->number)
            ->addColumn('document_date', fn (DamagedItemLine $line) => $line->document->document_date?->format('Y-m-d'))
            ->addColumn('code', fn (DamagedItemLine $line) => $line->product->code)
            ->addColumn('name', fn (DamagedItemLine $line) => $line->product->name)
            ->addColumn('unit', fn (DamagedItemLine $line) => $line->product->unit?->symbol ?: $line->product->unit?->name ?: '—')
            ->addColumn('actions', function (DamagedItemLine $line) {
                $items = '';
                if (auth()->user()->can('damaged-items.update')) {
                    $items .= '<li><button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-edit-damaged-item" data-id="'.(int)$line->id.'" data-number="'.e($line->document->number).'" data-date="'.$line->document->document_date?->format('Y-m-d').'" data-product="'.(int)$line->product_id.'" data-code="'.e($line->product->code).'" data-name="'.e($line->product->name).'" data-unit="'.e($line->product->unit?->symbol ?: $line->product->unit?->name ?: '').'" data-quantity="'.e($line->quantity).'" data-description="'.e($line->description).'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i>'.e(__('ui.common.edit')).'</button></li>';
                }
                if (auth()->user()->can('damaged-items.delete')) {
                    $items .= '<li><button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-damaged-item" data-url="'.e(route('super.damaged-items.destroy', $line)).'" title="'.e(__('ui.common.delete')).'"><i data-feather="trash-2" class="icon-sm"></i>'.e(__('ui.common.delete')).'</button></li>';
                }
                if ($items === '') return '';
                return '<div class="dropdown d-inline-block"><button class="btn btn-sm bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="'.e(__('ui.common.actions')).'"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm">'.$items.'</ul></div>';
            })
            ->rawColumns(['actions'])->toJson();
    }

    public function productsDt(DamagedItemTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn (Product $product) => '<button type="button" class="btn btn-sm btn-primary btn-select-damaged-product" data-id="'.(int)$product->id.'" data-code="'.e($product->code).'" data-name="'.e($product->name).'" data-unit="'.e($product->unit?->symbol ?: $product->unit?->name ?: '').'"><i data-feather="check"></i></button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function store(Request $request, SaveDamagedItemAction $action): JsonResponse
    {
        $line = $action->create($this->validated($request), (int)$request->user()->id);
        return response()->json(['message' => __('ui.damage.saved'), 'id' => $line->id, 'number' => $line->document->number], 201);
    }

    public function update(Request $request, DamagedItemLine $damagedItemLine, SaveDamagedItemAction $action): JsonResponse
    {
        $action->update($damagedItemLine, $this->validated($request), (int)$request->user()->id);
        return response()->json(['message' => __('ui.damage.updated')]);
    }

    public function destroy(Request $request, DamagedItemLine $damagedItemLine, SaveDamagedItemAction $action): JsonResponse
    {
        $action->delete($damagedItemLine, (int) $request->user()->id);
        return response()->json(['message' => __('ui.damage.deleted')]);
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
