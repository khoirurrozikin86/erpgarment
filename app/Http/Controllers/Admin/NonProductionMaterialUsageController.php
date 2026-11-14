<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions\SaveNonProductionMaterialUsageAction;
use App\Domain\Inventory\Queries\NonProductionMaterialUsageTableQuery;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Exports\NonProductionMaterialUsagesExport;
use App\Http\Controllers\Controller;
use App\Models\{NonProductionMaterialUsageDocument, NonProductionMaterialUsageItem, Product};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class NonProductionMaterialUsageController extends Controller
{
    public function index(): View { return view('super.non-production-material-usages.index'); }

    public function numberPreview(DocumentNumberService $numbers): JsonResponse
    {
        return response()->json(['number' => $numbers->preview('non_production_material_usage')]);
    }

    public function export(Request $request, CompanyContext $company)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.non_production_usage.invalid_date_range'));
            }],
        ]);
        return Excel::download(
            new NonProductionMaterialUsagesExport($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null),
            'non-production-material-usages.xlsx',
        );
    }

    public function dt(Request $request, CompanyContext $company, NonProductionMaterialUsageTableQuery $query): JsonResponse
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) $fail(__('ui.non_production_usage.invalid_date_range'));
            }],
        ]);
        return DataTables::eloquent($query->builder($company->id(), $filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('number', fn (NonProductionMaterialUsageItem $item) => $item->document?->number)
            ->addColumn('transaction_date', fn (NonProductionMaterialUsageItem $item) => $item->document?->transaction_date?->format('Y-m-d'))
            ->addColumn('code', fn (NonProductionMaterialUsageItem $item) => $item->product?->code)
            ->addColumn('name', fn (NonProductionMaterialUsageItem $item) => $item->product?->name)
            ->addColumn('unit', fn (NonProductionMaterialUsageItem $item) => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—')
            ->addColumn('style', fn (NonProductionMaterialUsageItem $item) => $item->product?->style_code ?: '—')
            ->addColumn('actions', function (NonProductionMaterialUsageItem $item) {
                $links = '';
                if (auth()->user()->can('non-production-material-usages.update')) {
                    $links .= '<li><button type="button" class="dropdown-item d-flex align-items-center gap-2 btn-edit-np-usage" data-id="'.(int)$item->id.'" data-number="'.e($item->document?->number).'" data-date="'.$item->document?->transaction_date?->format('Y-m-d').'" data-product="'.(int)$item->product_id.'" data-code="'.e($item->product?->code).'" data-name="'.e($item->product?->name).'" data-unit="'.e($item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '').'" data-quantity="'.e($item->quantity).'" data-price="'.e($item->unit_price).'" title="'.e(__('ui.common.edit')).'"><i data-feather="edit-2" class="icon-sm"></i>'.e(__('ui.common.edit')).'</button></li>';
                }
                if (auth()->user()->can('non-production-material-usages.delete')) {
                    $links .= '<li><button type="button" class="dropdown-item d-flex align-items-center gap-2 text-danger btn-delete-np-usage" data-url="'.e(route('super.non-production-material-usages.destroy', $item)).'" data-number="'.e($item->document?->number).'"><i data-feather="trash-2" class="icon-sm"></i>'.e(__('ui.common.delete')).'</button></li>';
                }
                return $links ? '<div class="dropdown d-inline-block"><button class="btn btn-sm bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="'.e(__('ui.common.actions')).'"><i data-feather="more-vertical" class="icon-sm"></i></button><ul class="dropdown-menu dropdown-menu-end shadow-sm">'.$links.'</ul></div>' : '';
            })
            ->rawColumns(['actions'])->toJson();
    }

    public function productsDt(NonProductionMaterialUsageTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->products())
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name ?: '—')
            ->addColumn('actions', fn (Product $product) => '<button type="button" class="btn btn-sm btn-primary btn-select-np-usage-product" data-id="'.(int)$product->id.'" data-code="'.e($product->code).'" data-name="'.e($product->name).'" data-unit="'.e($product->unit?->symbol ?: $product->unit?->name ?: '').'"><i data-feather="check" class="icon-sm"></i></button>')
            ->rawColumns(['actions'])->toJson();
    }

    public function store(Request $request, SaveNonProductionMaterialUsageAction $action): JsonResponse
    {
        $item = $action->create($this->validated($request), (int) $request->user()->id);
        return response()->json(['message' => __('ui.non_production_usage.saved'), 'number' => $item->document?->number], 201);
    }

    public function update(Request $request, NonProductionMaterialUsageItem $usageItem, CompanyContext $company, SaveNonProductionMaterialUsageAction $action): JsonResponse
    {
        abort_unless($usageItem->document()->where('company_id', $company->id())->exists(), 404);
        $action->update($usageItem, $this->validated($request), (int) $request->user()->id);
        return response()->json(['message' => __('ui.non_production_usage.updated')]);
    }

    public function destroy(Request $request, NonProductionMaterialUsageItem $usageItem, CompanyContext $company, SaveNonProductionMaterialUsageAction $action): JsonResponse
    {
        abort_unless($usageItem->document()->where('company_id', $company->id())->exists(), 404);
        $action->delete($usageItem, (int) $request->user()->id);
        return response()->json(['message' => __('ui.non_production_usage.deleted')]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'transaction_date' => ['required', 'date_format:Y-m-d'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'unit_price' => ['required', 'numeric', 'gte:0', 'max:999999999999.9999'],
        ]);
    }
}
