<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Purchasing\Services\MaterialRequestService;
use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Purchasing\Queries\MaterialRequestTableQuery;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\MaterialRequest;
use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\ProductionOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class MaterialRequestController extends Controller
{
    public function index(CompanyContext $company)
    {
        $products = Product::query()->where('is_active', true)->orderBy('name')
            ->get(['id', 'code', 'name', 'unit_id'])->load('unit:id,name,symbol')
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => $product->code . ' — ' . $product->name,
                'unit' => $product->unit?->symbol ?: $product->unit?->name,
            ]);

        $productionOrders = ProductionOrder::query()->where('company_id', $company->id())
            ->whereIn('status', ['released', 'in_progress'])->with('outputProduct:id,code,name')
            ->orderByDesc('created_at')->get(['id', 'number', 'output_product_id', 'planned_quantity', 'bom_version'])
            ->map(fn (ProductionOrder $order) => [
                'id' => $order->id,
                'label' => $order->number . ' — ' . $order->outputProduct?->code . ' ' . $order->outputProduct?->name . ' (' . number_format((float) $order->planned_quantity, 4, ',', '.') . ')',
            ]);

        return view('super.material-requests.index', compact('products', 'productionOrders'));
    }

    public function productionOrderRequirements(ProductionOrder $productionOrder, CompanyContext $company): JsonResponse
    {
        $order = ProductionOrder::query()->where('company_id', $company->id())
            ->whereIn('status', ['released', 'in_progress'])->with('materials.product.unit')
            ->findOrFail($productionOrder->id);
        $items = $order->materials->groupBy('product_id')->map(function ($materials) use ($order) {
            $first = $materials->first();
            $quantity = $materials->sum(fn ($material) => max(0, (float) $material->required_quantity - (float) $material->issued_quantity));
            $operations = $materials->pluck('operation')->filter()->unique()->implode(', ');

            return [
                'product_id' => $first->product_id,
                'quantity' => round($quantity, 4),
                'note' => __('ui.workflow.production_order_material_note', ['number' => $order->number, 'operations' => $operations ?: '—']),
                'label' => $first->product?->code . ' — ' . $first->product?->name,
                'unit' => $first->product?->unit?->symbol ?: $first->product?->unit?->name,
            ];
        })->filter(fn ($item) => $item['quantity'] > 0)->values();

        return response()->json([
            'production_order_id' => $order->id,
            'production_order_number' => $order->number,
            'bill_of_material_id' => $order->bill_of_material_id,
            'bom_version' => $order->bom_version,
            'output_product_id' => $order->output_product_id,
            'production_quantity' => (float) $order->planned_quantity,
            'output_product' => $order->outputProduct?->name,
            'items' => $items,
        ]);
    }

    public function bomRequirements(Request $request, CompanyContext $company): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'production_quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'needed_at' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $date = $data['needed_at'] ?? now()->toDateString();
        $bom = BillOfMaterial::query()->where('company_id', $company->id())
            ->where('product_id', $data['product_id'])->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->with(['product.unit', 'items.product.unit'])->first();

        if (!$bom || (float) $bom->output_quantity <= 0 || $bom->items->isEmpty()) {
            return response()->json(['message' => __('ui.workflow.active_bom_not_found')], 422);
        }

        $ratio = (float) $data['production_quantity'] / (float) $bom->output_quantity;
        $items = $bom->items->groupBy('product_id')->map(function ($bomItems) use ($ratio, $bom) {
            $first = $bomItems->first();
            $quantity = $bomItems->sum(fn ($item) => (float) $item->quantity * $ratio * (1 + (float) $item->wastage_percent / 100));
            $operations = $bomItems->pluck('operation')->filter()->unique()->implode(', ');

            return [
                'product_id' => $first->product_id,
                'quantity' => round($quantity, 4),
                'note' => $operations === '' ? __('ui.workflow.generated_from_bom', ['version' => $bom->version]) : __('ui.workflow.bom_operations_note', ['operations' => $operations]),
                'label' => $first->product?->code . ' — ' . $first->product?->name,
                'unit' => $first->product?->unit?->symbol ?: $first->product?->unit?->name,
            ];
        })->values();

        return response()->json([
            'bill_of_material_id' => $bom->id,
            'bom_version' => $bom->version,
            'output_product_id' => $bom->product_id,
            'output_product' => $bom->product?->name,
            'output_unit' => $bom->product?->unit?->symbol ?: $bom->product?->unit?->name,
            'production_quantity' => (float) $data['production_quantity'],
            'items' => $items,
        ]);
    }

    public function dt(Request $request, MaterialRequestTableQuery $query)
    {
        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', function ($attribute, $value, $fail) use ($request) {
                if ($value && $request->input('from_date') && $value < $request->input('from_date')) {
                    $fail('Tanggal akhir harus sama atau setelah tanggal awal.');
                }
            }],
        ]);

        return DataTables::eloquent($query->builder($filters['from_date'] ?? null, $filters['to_date'] ?? null))
            ->addColumn('requester_name', fn (MaterialRequest $request) => $request->requester?->name ?? __('ui.workflow.deleted_user'))
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
                $buttons = '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-request" data-url="' . e(route('super.material-requests.show', $request)) . '" title="' . e(__('ui.workflow.view_detail')) . '"><i data-feather="eye"></i></button>';
                $remaining = (float) $request->items_total_quantity - (float) $request->items_issued_quantity;
                if ($request->status === 'approved' && $remaining > 0.0000001 && auth()->user()->can('material-requests.issue')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-view-request btn-issue-request" data-url="' . e(route('super.material-requests.show', $request)) . '" title="' . e(__('ui.workflow.issue_materials')) . '">' . e(__('ui.workflow.issue_materials')) . '</button>';
                }
                $buttons .= ' <a class="btn btn-sm btn-outline-primary" href="' . e(route('super.material-requests.pdf', $request)) . '" target="_blank" rel="noopener" title="' . e(__('ui.workflow.view_pdf')) . '"><i data-feather="file-text"></i></a>';
                $buttons .= ' <a class="btn btn-sm btn-outline-secondary" href="' . e(route('super.material-requests.pdf.download', $request)) . '" title="' . e(__('ui.workflow.download_pdf')) . '"><i data-feather="download"></i></a>';
                if ($request->status === 'pending' && auth()->user()->can('material-requests.approve')) {
                    $buttons .= ' <button type="button" class="btn btn-sm btn-success btn-review-request" data-status="approved" data-url="' . e(route('super.material-requests.approve', $request)) . '" title="' . e(__('ui.workflow.approve')) . '"><i data-feather="check"></i></button>';
                    $buttons .= ' <button type="button" class="btn btn-sm btn-outline-danger btn-review-request" data-status="rejected" data-url="' . e(route('super.material-requests.reject', $request)) . '" title="' . e(__('ui.workflow.reject')) . '"><i data-feather="x"></i></button>';
                }
                return $buttons;
            })
            ->rawColumns(['status_label', 'purchase_status_label', 'actions'])
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
            'items.*.product_id' => ['required', 'distinct', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($data['production_order_id'])) {
            $order = ProductionOrder::query()->where('company_id', $company->id())
                ->whereIn('status', ['released', 'in_progress'])->find($data['production_order_id']);
            if (!$order) {
                throw \Illuminate\Validation\ValidationException::withMessages(['production_order_id' => __('ui.workflow.production_order_not_available')]);
            }
            $data['bill_of_material_id'] = $order->bill_of_material_id;
            $data['output_product_id'] = $order->output_product_id;
            $data['bom_version'] = $order->bom_version;
            $data['production_quantity'] = (float) $order->planned_quantity;
        } elseif (!empty($data['bill_of_material_id'])) {
            $date = $data['needed_at'] ?? now()->toDateString();
            $bom = BillOfMaterial::query()->where('company_id', $company->id())
                ->whereKey($data['bill_of_material_id'])->where('status', 'active')
                ->where('product_id', $data['output_product_id'] ?? null)
                ->where(fn ($query) => $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date))
                ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
                ->first();
            if (!$bom || empty($data['production_quantity'])) {
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
            'output_product' => $materialRequest->outputProduct?->name,
            'production_quantity' => $materialRequest->production_quantity,
            'bom_version' => $materialRequest->bom_version,
            'production_order_id' => $materialRequest->production_order_id,
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
                'product' => $item->product?->name ?? 'Barang dihapus',
                'code' => $item->product?->code ?? '—',
                'quantity' => number_format((float) $item->quantity, 4, ',', '.'),
                'quantity_value' => (float) $item->quantity,
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
        $filename = $this->pdfFilename($materialRequest->number);
        $viewData = [...$branding->data(), 'materialRequest' => $materialRequest];

        return Pdf::loadView('super.material-requests.pdf', $viewData)
            ->setPaper('a4')
            ->stream($filename);
    }

    public function downloadPdf(MaterialRequest $materialRequest, MaterialRequestTableQuery $query, CompanyPdfBrandingService $branding)
    {
        $materialRequest = $query->details($materialRequest);
        $filename = $this->pdfFilename($materialRequest->number);
        $viewData = [...$branding->data(), 'materialRequest' => $materialRequest];

        return Pdf::loadView('super.material-requests.pdf', $viewData)
            ->setPaper('a4')
            ->download($filename);
    }

    private function pdfFilename(string $number): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', $number);
        return trim($safeNumber, '-_.') . '.pdf';
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_request_item_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999999999.9999'],
        ]);
        $service->issue($materialRequest, $data['items'], (int) $request->user()->id);

        return response()->json(['message' => __('ui.workflow.material_issue_success')]);
    }
}
