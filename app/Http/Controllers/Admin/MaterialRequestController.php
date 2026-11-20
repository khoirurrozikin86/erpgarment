<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Purchasing\Services\MaterialRequestService;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Queries\MaterialRequestTableQuery;
use App\Domain\Purchasing\Services\MaterialRequestPdfItems;
use App\Domain\Purchasing\Services\ProductionSourceSummary;
use App\Exports\MaterialRequestsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\ProductionOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class MaterialRequestController extends Controller
{
    public function index(CompanyContext $company)
    {
        $products = Product::query()->where('is_active', true)->orderBy('name')
            ->get(['id', 'code', 'name', 'unit_id', 'color', 'size', 'style_code', 'category_id', 'parent_product_id'])->load(['unit:id,name,symbol', 'category:id,code,name', 'parentProduct:id,code,name'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'parent_product_id' => $product->parent_product_id,
                'parent_label' => $product->parentProduct ? $product->parentProduct->code.' — '.$product->parentProduct->name : $product->code.' — '.$product->name,
                'code' => $product->code,
                'name' => $product->variant_display_name,
                'label' => $product->code . ' — ' . $product->variant_display_name,
                'unit' => $product->unit?->symbol ?: $product->unit?->name,
                'style' => $product->style_code, 'color' => $product->color, 'size' => $product->size,
                'finished_good' => $product->category?->code === 'FG' || strtolower($product->category?->name ?? '') === 'finished goods',
            ]);

        $activeBoms = BillOfMaterial::query()->where('company_id', $company->id())->where('status', 'active')
            ->with(['product:id,style_code'])->get();
        $requirements = app(\App\Domain\Purchasing\Services\MaterialOrderRequirements::class);
        $availableBoms = $activeBoms->map(fn (BillOfMaterial $bom) => [
                'id' => $bom->id, 'number' => $bom->number, 'version' => $bom->version,
                'style' => $bom->style_code ?: $bom->product?->style_code,
                'type' => __('variants.bom_type_'.$bom->bom_type),
            ])->values();
        $productionOrders = ProductionOrder::query()->where('company_id', $company->id())
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')->from('production_orders as pending')
                    ->whereColumn('pending.company_id', 'production_orders.company_id')
                    ->whereColumn('pending.number', 'production_orders.number')
                    ->whereNull('pending.output_product_id');
            })
            ->whereIn('status', ['released', 'in_progress'])->with(['outputProduct:id,code,name,color,size,style_code,unit_id,parent_product_id', 'outputProduct.unit:id,name,symbol', 'billOfMaterial'])
            ->orderByDesc('created_at')->orderByDesc('id')->get(['id', 'number', 'output_product_id', 'planned_quantity', 'bom_version', 'production_order_header_id', 'bill_of_material_id', 'style_code', 'order_color', 'order_size', 'po_date'])
            ->groupBy(fn ($order) => $order->production_order_header_id ?: $order->number)
            ->map(function ($details) use ($activeBoms, $requirements) {
                $order = $details->first();
                $availableBoms = $activeBoms->filter(fn (BillOfMaterial $bom) => $details->contains(
                    fn (ProductionOrder $line) => $requirements->matches($bom, $line)
                        && (!$bom->effective_from || $bom->effective_from->lte($line->po_date))
                        && (!$bom->effective_until || $bom->effective_until->gte($line->po_date))
                ));
                return ['id' => $order->id, 'number' => $order->number, 'line_ids' => $details->pluck('id')->values()->all(),
                    'boms' => $availableBoms->concat($details->map(fn ($line) => $line->billOfMaterial)->filter())->unique('id')->map(fn ($bom) => ['id' => $bom->id, 'number' => $bom->number, 'style' => $bom->style_code ?: $bom->product?->style_code, 'type' => __('variants.bom_type_'.$bom->bom_type), 'version' => $bom->version])->values()->all(),
                    'details' => $details->map(fn ($line) => [
                        'code' => $line->outputProduct?->code, 'name' => $line->outputProduct?->name,
                        'style' => $line->model_style, 'color' => $line->line_color,
                        'size' => $line->line_size, 'quantity' => (float) $line->planned_quantity,
                        'unit' => $line->outputProduct?->unit?->symbol ?: $line->outputProduct?->unit?->name,
                    ])->values()->all(),
                    'code' => $details->pluck('outputProduct.code')->unique()->implode(', '),
                    'name' => $details->pluck('outputProduct.name')->unique()->implode(', '),
                    'style' => $details->pluck('model_style')->filter()->unique()->implode(', '),
                    'color' => $details->pluck('line_color')->filter()->unique()->implode(', '),
                    'size' => $details->pluck('line_size')->filter()->unique()->implode(', '),
                    'quantity' => (float) $details->sum('planned_quantity'), 'label' => $order->number,
                ];
            })->values();

        return view('super.material-requests.index', compact('products', 'productionOrders', 'availableBoms'));
    }

    public function export(Request $request)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ]);
        $fromDate = $filters['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();

        return Excel::download(
            new MaterialRequestsExport($fromDate, $toDate),
            'material-requests-' . ($fromDate ?: 'all') . '-to-' . ($toDate ?: 'all') . '.xlsx'
        );
    }

    public function productionOrderRequirements(ProductionOrder $productionOrder, CompanyContext $company, Request $request): JsonResponse
    {
        $order = ProductionOrder::query()->where('company_id', $company->id())
            ->whereIn('status', ['released', 'in_progress'])->with('materials.product.unit')
            ->findOrFail($productionOrder->id);
        $input = $request->validate(['bill_of_material_id' => ['nullable', 'integer', 'min:1']]);
        return response()->json(app(\App\Domain\Purchasing\Services\MaterialOrderRequirements::class)->resolve($order, $company->id(), isset($input['bill_of_material_id']) ? (int) $input['bill_of_material_id'] : null));
    }

    public function bomRequirements(Request $request, CompanyContext $company): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'production_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'bill_of_material_id' => ['nullable', 'integer', 'min:1'],
            'needed_at' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $date = $data['needed_at'] ?? now()->toDateString();
        $bom = app(\App\Domain\Manufacturing\Services\ResolveProductionBom::class)
            ->resolve($company->id(), (int) $data['product_id'], $date, isset($data['bill_of_material_id']) ? (int) $data['bill_of_material_id'] : null);
        $output = Product::with('unit')->findOrFail($data['product_id']);

        $ratio = (float) $data['production_quantity'] / (float) $bom->output_quantity;
        $items = $bom->items->groupBy('product_id')->map(function ($bomItems) use ($ratio, $bom) {
            $first = $bomItems->first();
            $quantity = $bomItems->sum(fn ($item) => (float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100));
            $operations = $bomItems->pluck('operation')->filter()->unique()->implode(', ');

            return [
                'product_id' => $first->product_id,
                'quantity' => round($quantity, 4),
                'note' => $operations === '' ? __('ui.workflow.generated_from_bom', ['version' => $bom->version]) : __('ui.workflow.bom_operations_note', ['operations' => $operations]),
                'label' => $first->product?->code . ' — ' . $first->product?->variant_display_name,
                'unit' => $first->product?->unit?->symbol ?: $first->product?->unit?->name,
            ];
        })->values();

        return response()->json([
            'bill_of_material_id' => $bom->id,
            'bom_number' => $bom->number,
            'bom_type' => __('variants.bom_type_'.$bom->bom_type),
            'bom_version' => $bom->version,
            'output_product_id' => $output->id,
            'output_product' => $output->variant_display_name,
            'output_unit' => $output->unit?->symbol ?: $output->unit?->name,
            'source_details' => [[
                'style' => $output->style_code, 'color' => $output->color, 'size' => $output->size,
                'quantity' => (float) $data['production_quantity'],
                'materials' => $bom->items->map(fn ($item) => [
                    'style' => $output->style_code, 'color' => $item->applies_to_color ?: $item->product?->color, 'size' => $item->applies_to_size ?: $item->product?->size,
                    'product_id' => $item->product_id, 'operation' => $item->operation,
                    'code' => $item->product?->code, 'name' => $item->product?->name,
                    'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name,
                    'order_quantity' => (float) $data['production_quantity'], 'consumption' => (float) $item->quantity,
                    'bom_output_quantity' => (float) $bom->output_quantity, 'allowance_percent' => (float) $item->wastage_percent,
                    'required_quantity' => round((float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100), 4),
                    'issued_quantity' => 0,
                    'request_quantity' => round((float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100), 4),
                ])->values()->all(),
            ]],
            'production_quantity' => (float) $data['production_quantity'],
            'items' => $items,
        ]);
    }

    public function dt(Request $request, MaterialRequestTableQuery $query, ProductionSourceSummary $sourceSummary)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ]);

        return DataTables::eloquent($query->builder($filters['from_date'] ?? now()->startOfMonth()->toDateString(), $filters['to_date'] ?? now()->toDateString()))
            ->addColumn('requester_name', fn (MaterialRequest $request) => $request->requester?->name ?? __('ui.workflow.deleted_user'))
            ->addColumn('buyer_po_number', fn (MaterialRequest $request) => $request->productionOrder?->buyer_po_number ?: '—')
            ->addColumn('product_label', function (MaterialRequest $request) use ($sourceSummary) {
                $lines = $sourceSummary->lines($request);
                if ($lines->isEmpty()) {
                    return '—';
                }
                return $lines->map(function (array $line) {
                    $style = e($line['style'] ?: '—');
                    $color = e($line['color'] ?: '—');
                    $size = e($line['size'] ?: '—');
                    $quantity = $line['quantity'] === null
                        ? '—'
                        : \App\Support\DisplayNumber::format((float) $line['quantity'], 4);
                    return '<span class="d-block text-nowrap">' . $style . ' / ' . $color . ' / ' . $size . ' / ' . $quantity . '</span>';
                })->implode('');
            })
            ->addColumn('production_quantity_display', function (MaterialRequest $request) {
                $sourceQuantities = collect($request->source_details ?? [])->pluck('quantity');
                $quantity = $sourceQuantities->isNotEmpty()
                    ? $sourceQuantities->sum()
                    : ($request->productionOrderHeader?->details->sum('planned_quantity')
                        ?? $request->productionOrder?->planned_quantity
                        ?? $request->production_quantity);

                return $quantity === null ? '—' : \App\Support\DisplayNumber::format((float) $quantity, 4);
            })
            ->addColumn('required_items', function (MaterialRequest $request) {
                if ($request->items->isEmpty()) return '—';
                $items = $request->items->map(function ($item) {
                    $code = e($item->product?->code ?? '—');
                    $name = e($item->product?->variant_display_name ?? __('ui.workflow.deleted_item'));
                    $quantity = \App\Support\DisplayNumber::format((float) $item->quantity, 4);
                    $unit = $item->product?->unit?->symbol ?: $item->product?->unit?->name;
                    $dimensions = collect([
                        __('ui.workflow.color') => filled($item->color) ? $item->color : $item->product?->color,
                        __('ui.workflow.size') => filled($item->size) ? $item->size : $item->product?->size,
                    ])->filter(fn ($value) => filled($value))
                        ->map(fn ($value, $label) => $label . ' ' . trim($value))
                        ->implode(' / ');

                    return '<span class="badge rounded-pill bg-light text-secondary border fw-normal" title="' . $name . '">'
                        . $code . ($dimensions !== '' ? ' (' . e($dimensions) . ')' : '')
                        . ' × ' . $quantity . ($unit ? ' ' . e($unit) : '') . '</span>';
                })->implode(' ');

                return '<div class="d-flex flex-wrap gap-1" style="max-width:24rem">' . $items . '</div>';
            })
            ->addColumn('items_count', fn (MaterialRequest $request) => $request->items_count)
            ->addColumn('status_label', fn (MaterialRequest $request) => match ($request->status) {
                'approved' => '<span class="badge bg-success">' . __('ui.workflow.approval_approved') . '</span>',
                'rejected' => '<span class="badge bg-danger">' . __('ui.workflow.approval_rejected') . '</span>',
                default => '<span class="badge bg-warning text-dark">' . __('ui.workflow.approval_pending') . '</span>',
            })
            ->addColumn('purchase_status_label', fn (MaterialRequest $request) => match ($request->purchase_status) {
                'processed' => '<span class="badge bg-success">' . __('ui.workflow.allocated') . '</span>',
                'partially_processed' => '<span class="badge bg-info text-dark">' . __('ui.workflow.partially_allocated') . '</span>',
                default => '<span class="badge bg-secondary">' . __('ui.workflow.not_processed') . '</span>',
            })
            ->addColumn('actions', function (MaterialRequest $request) {
                $detailUrl = e(route('super.material-requests.show', $request));
                $buttons = '<div class="d-inline-flex align-items-center justify-content-end gap-2 text-nowrap">'
                    . '<button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-view-request" data-url="' . $detailUrl . '" title="' . e(__('ui.workflow.view_detail')) . '" aria-label="' . e(__('ui.workflow.view_detail')) . '"><i data-feather="eye" class="icon-sm"></i></button>';
                $buttons .= '<a class="btn btn-link btn-sm p-0 text-primary" href="' . e(route('super.material-requests.pdf', $request)) . '" target="_blank" rel="noopener" title="' . e(__('ui.workflow.view_pdf')) . '" aria-label="' . e(__('ui.workflow.view_pdf')) . '"><i data-feather="file-text" class="icon-sm"></i></a>'
                    . '<a class="btn btn-link btn-sm p-0 text-secondary" href="' . e(route('super.material-requests.pdf.download', $request)) . '" title="' . e(__('ui.workflow.download_pdf')) . '" aria-label="' . e(__('ui.workflow.download_pdf')) . '"><i data-feather="download" class="icon-sm"></i></a>';
                if ($request->status === 'pending' && auth()->user()->can('material-requests.approve')) {
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-success btn-review-request" data-status="approved" data-url="' . e(route('super.material-requests.approve', $request)) . '" title="' . e(__('ui.workflow.approve')) . '" aria-label="' . e(__('ui.workflow.approve')) . '"><i data-feather="check" class="icon-sm"></i></button>'
                        . '<button type="button" class="btn btn-link btn-sm p-0 text-danger btn-review-request" data-status="rejected" data-url="' . e(route('super.material-requests.reject', $request)) . '" title="' . e(__('ui.workflow.reject')) . '" aria-label="' . e(__('ui.workflow.reject')) . '"><i data-feather="x" class="icon-sm"></i></button>';
                }
                if (auth()->user()->can('material-requests.delete') && !$request->hasDownstreamTransactions()) {
                    $delete = e(__('ui.common.delete'));
                    $buttons .= '<button type="button" class="btn btn-link btn-sm p-0 text-danger btn-delete-request" data-url="' . e(route('super.material-requests.destroy', $request)) . '" title="' . $delete . '" aria-label="' . $delete . '"><i data-feather="trash-2" class="icon-sm"></i></button>';
                }
                return $buttons . '</div>';
            })
            ->rawColumns(['product_label', 'required_items', 'status_label', 'purchase_status_label', 'actions'])
            ->toJson();
    }

    public function store(Request $request, MaterialRequestService $service, CompanyContext $company): JsonResponse
    {
        $data = $request->validate([
            'production_order_id' => ['nullable', 'integer', 'exists:production_orders,id'],
            'bill_of_material_id' => ['nullable', 'integer', 'exists:bill_of_materials,id'],
            'output_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'bom_version' => ['nullable', 'string', 'max:30'],
            'production_quantity' => ['nullable', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'department' => ['nullable', 'string', 'max:120'],
            'needed_at' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'items.*.color' => ['nullable', 'string', 'max:80'],
            'items.*.size' => ['nullable', 'string', 'max:40'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($data['production_order_id'])) {
            if (empty($data['bill_of_material_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['bill_of_material_id' => __('variants.choose_bom_first')]);
            }
            $order = ProductionOrder::query()->where('company_id', $company->id())
                ->whereIn('status', ['released', 'in_progress'])->find($data['production_order_id']);
            if (!$order) {
                throw \Illuminate\Validation\ValidationException::withMessages(['production_order_id' => __('ui.workflow.production_order_not_available')]);
            }
            $requirements = app(\App\Domain\Purchasing\Services\MaterialOrderRequirements::class)->resolve($order, $company->id(), !empty($data['bill_of_material_id']) ? (int) $data['bill_of_material_id'] : null);
            foreach (['production_order_id', 'production_order_header_id', 'bill_of_material_id', 'output_product_id', 'bom_version', 'production_quantity', 'source_details'] as $key) $data[$key] = $requirements[$key];
            // Allow splitting a base material into requested variants, but never change MO totals.
            $expected = collect($requirements['items'])->mapWithKeys(fn ($line) => [(int) $line['product_id'] => round((float) $line['quantity'], 4)])->all();
            $submitted = collect($data['items'])->groupBy('product_id')->map(fn ($lines) => round($lines->sum('quantity'), 4))->all();
            ksort($expected); ksort($submitted);
            if ($expected != $submitted) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => __('variants.mo_material_totals')]);
            }
            foreach ($data['items'] as $line) {
                $material = Product::findOrFail($line['product_id']);
                if ($material->parent_product_id && (mb_strtolower(trim($line['color'] ?? '')) !== mb_strtolower(trim($material->color ?? '')) || mb_strtolower(trim($line['size'] ?? '')) !== mb_strtolower(trim($material->size ?? '')))) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['items' => __('variants.mo_material_identity')]);
                }
            }
            if (!$data['items']) throw \Illuminate\Validation\ValidationException::withMessages(['items' => __('ui.workflow.production_order_not_available')]);
        } elseif (!empty($data['bill_of_material_id'])) {
            $date = $data['needed_at'] ?? now()->toDateString();
            $bom = app(\App\Domain\Manufacturing\Services\ResolveProductionBom::class)
                ->resolve($company->id(), (int) ($data['output_product_id'] ?? 0), $date, (int) $data['bill_of_material_id']);
            if ((int) $data['bill_of_material_id'] !== $bom->id || empty($data['production_quantity'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['bill_of_material_id' => __('ui.workflow.active_bom_not_found')]);
            }
            $data['bom_version'] = $bom->version;
        } else {
            $data['output_product_id'] = null;
            $data['bom_version'] = null;
            $data['production_quantity'] = null;
        }

        $materialRequest = $service->create($data, (int) $request->user()->id);

        return response()->json(['message' => __('ui.workflow.request_created_message'), 'id' => $materialRequest->id], 201);
    }

    public function show(MaterialRequest $materialRequest, MaterialRequestTableQuery $query): JsonResponse
    {
        $materialRequest = $query->details($materialRequest);
        return response()->json([
            'id' => $materialRequest->id,
            'issue_url' => route('super.material-requests.issue', $materialRequest),
            'number' => $materialRequest->number,
            'output_product' => $materialRequest->outputProduct?->variant_display_name,
            'production_quantity' => $materialRequest->production_quantity,
            'bom_version' => $materialRequest->bom_version,
            'bom_number' => $materialRequest->billOfMaterial?->number,
            'bom_type' => $materialRequest->billOfMaterial ? __('variants.bom_type_'.$materialRequest->billOfMaterial->bom_type) : null,
            'production_order_id' => $materialRequest->production_order_id,
            'source_details' => $materialRequest->source_details ?? [],
            'production_order_number' => $materialRequest->productionOrder?->number,
            'requester' => $materialRequest->requester?->name ?? 'User dihapus',
            'department' => $materialRequest->department ?: '—',
            'needed_at' => $materialRequest->needed_at?->format('d/m/Y') ?? '—',
            'reason' => $materialRequest->reason,
            'status' => $materialRequest->status,
            'purchase_status' => $materialRequest->purchase_status,
            'reviewer' => $materialRequest->reviewer?->name ?? '—',
            'reviewed_at' => $materialRequest->reviewed_at?->format('d/m/Y H:i') ?? '—',
            'review_note' => $materialRequest->review_note ?: '—',
            'items' => $materialRequest->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => $item->product?->variant_display_name ?? 'Barang dihapus',
                'code' => $item->product?->code ?? '—',
                'quantity' => \App\Support\DisplayNumber::format((float) $item->quantity, 4),
                'quantity_value' => (float) $item->quantity,
                'color' => $item->color,
                'size' => $item->size,
                'issued_quantity' => (float) $item->issued_quantity,
                'remaining_quantity' => max(0, (float) $item->quantity - (float) $item->issued_quantity),
                'unit' => $item->product?->unit?->symbol ?: $item->product?->unit?->name ?: '—',
                'note' => $item->note ?: '—',
            ]),
        ]);
    }

    public function pdf(MaterialRequest $materialRequest, MaterialRequestTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $materialRequest = $query->details($materialRequest);
        $materialRequest->load([
            'productionOrder.outputProduct',
            'productionOrderHeader.details.outputProduct',
            'outputProduct',
        ]);
        $filename = $this->pdfFilename($materialRequest->number);
        $pdfItems = app(MaterialRequestPdfItems::class)->forPdf($materialRequest->items);
        $productionDetails = app(ProductionSourceSummary::class)->lines($materialRequest);
        $viewData = [...$branding->data(), 'materialRequest' => $materialRequest, 'pdfItems' => $pdfItems, 'productionDetails' => $productionDetails];

        return Pdf::loadView('super.material-requests.pdf', $viewData)
            ->setPaper('a4')
            ->stream($filename);
    }

    public function downloadPdf(MaterialRequest $materialRequest, MaterialRequestTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $materialRequest = $query->details($materialRequest);
        $materialRequest->load([
            'productionOrder.outputProduct',
            'productionOrderHeader.details.outputProduct',
            'outputProduct',
        ]);
        $filename = $this->pdfFilename($materialRequest->number);
        $pdfItems = app(MaterialRequestPdfItems::class)->forPdf($materialRequest->items);
        $productionDetails = app(ProductionSourceSummary::class)->lines($materialRequest);
        $viewData = [...$branding->data(), 'materialRequest' => $materialRequest, 'pdfItems' => $pdfItems, 'productionDetails' => $productionDetails];

        return Pdf::loadView('super.material-requests.pdf', $viewData)
            ->setPaper('a4')
            ->download($filename);
    }

    private function pdfFilename(string $number): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', $number);
        return trim($safeNumber, '-_.') . '.pdf';
    }

    public function destroy(MaterialRequest $materialRequest, AuditLogService $audit): JsonResponse
    {
        DB::transaction(function () use ($materialRequest, $audit) {
            $locked = MaterialRequest::lockForUpdate()->findOrFail($materialRequest->id);
            $request = MaterialRequest::withDeletionBlockers()->with('items')->findOrFail($locked->id);
            if ($request->hasDownstreamTransactions()) {
                throw ValidationException::withMessages(['material_request' => __('variants.mr_delete_locked')]);
            }

            $before = $request->toArray();
            $locked->delete();
            $audit->log(
                action: 'DELETE', module: 'MATERIAL_REQUEST',
                description: __('variants.mr_deleted').' '.$locked->number,
                model: $locked, oldValues: $before, newValues: null,
            );
        });

        return response()->json(['message' => __('variants.mr_deleted')]);
    }

    public function approve(Request $request, MaterialRequest $materialRequest, MaterialRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $service->review($materialRequest, (int) $request->user()->id, 'approved', $data['note'] ?? null);
        return response()->json(['message' => __('ui.workflow.request_approved_message')]);
    }

    public function reject(Request $request, MaterialRequest $materialRequest, MaterialRequestService $service): JsonResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $service->review($materialRequest, (int) $request->user()->id, 'rejected', $data['note']);
        return response()->json(['message' => __('ui.workflow.request_rejected_message')]);
    }

    public function issue(Request $request, MaterialRequest $materialRequest, MaterialRequestService $service): JsonResponse
    {
        $data = $request->validate([
            'issue_date' => ['nullable', 'date_format:Y-m-d'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_request_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ]);
        $service->issue($materialRequest, $data['items'], (int) $request->user()->id, $data['issue_date'] ?? null);

        return response()->json(['message' => __('ui.workflow.material_issue_success')]);
    }
}
