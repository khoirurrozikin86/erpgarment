<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Customs\Queries\BcDocumentVariantQuery;
use App\Http\Controllers\Controller;
use App\Models\CustomsDocument;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class BcPickerController extends Controller
{
    public function documents(Request $request, CompanyContext $company): JsonResponse
    {
        $request->validate([
            'draw' => ['required', 'integer', 'min:0'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'in:10,25,50'],
            'search.value' => ['nullable', 'string', 'max:100'],
            'from_date' => ['required', 'date_format:Y-m-d'],
            'to_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);
        $dates = $request->only(['from_date', 'to_date']);
        $module = $request->route()->defaults['bcModule'];
        $direction = $request->route()->defaults['bcDirection'];
        $type = $request->route()->defaults['bcDocumentType'];
        $documents = CustomsDocument::query()
            ->leftJoin('suppliers', 'suppliers.id', '=', 'customs_documents.supplier_id')
            ->leftJoin('customers', 'customers.id', '=', 'customs_documents.customer_id')
            ->leftJoin('subcontractors', 'subcontractors.id', '=', 'customs_documents.subcontractor_id')
            ->where('customs_documents.company_id', $company->id())
            ->where('customs_documents.document_type', $type)
            ->whereBetween('customs_documents.document_date', [$dates['from_date'], $dates['to_date']])
            ->select([
                'customs_documents.id', 'customs_documents.submission_number', 'customs_documents.bl_awb_number',
                'customs_documents.bl_awb_date', 'customs_documents.document_number', 'customs_documents.document_date',
                'customs_documents.invoice_number', 'customs_documents.invoice_date',
            ])
            ->selectRaw('COALESCE(suppliers.name, customers.name, subcontractors.name) as party_name');

        return DataTables::eloquent($documents)
            ->filterColumn('party_name', fn ($query, $keyword) => $query->whereRaw(
                'COALESCE(suppliers.name, customers.name, subcontractors.name) LIKE ?', ["%{$keyword}%"]
            ))
            ->addColumn('edit_url', fn (CustomsDocument $document) => route('super.'.$direction.'.'.$module.'.edit', $document))
            ->escapeColumns([])
            ->toJson();
    }

    public function detail(Request $request, CompanyContext $company, CustomsDocument $document): JsonResponse
    {
        abort_unless(
            (int) $document->company_id === $company->id()
            && $document->document_type === $request->route()->defaults['bcDocumentType'],
            404
        );

        $items = DB::table('customs_document_items as i')
            ->where('i.document_id', $document->id)
            ->select([
                'i.document_id', 'i.item_code', 'i.item_name', 'i.unit', 'i.quantity', 'i.unit_price',
                'i.total_price', 'i.receipt_number', 'i.receipt_date', 'i.shipment_number', 'i.shipment_date',
                'i.cif', 'i.country_code', 'i.hs_code', 'i.tax_invoice_number', 'i.tax_invoice_date',
                'i.item_per_unit',
            ]);

        return response()->json([
            'submission_number' => $document->submission_number,
            'document_number' => $document->document_number,
            'items' => BcDocumentVariantQuery::rows($items, null, 'receipt_number', 'item_name')->get(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $request->validate([
            'draw' => ['required', 'integer', 'min:0'],
            'start' => ['required', 'integer', 'min:0'],
            'length' => ['required', 'integer', 'in:10,25,50'],
            'search.value' => ['nullable', 'string', 'max:100'],
        ]);

        $products = Product::query()
            ->select(['id', 'code', 'name', 'unit_id', 'parent_product_id', 'color', 'size'])
            ->with(['unit:id,name,symbol', 'parentProduct:id,code'])
            ->where('is_active', true);

        return DataTables::eloquent($products)
            ->addColumn('parent_code', fn (Product $product) => $product->parentProduct?->code ?? $product->code)
            ->addColumn('sku_code', fn (Product $product) => $product->parent_product_id ? $product->code : null)
            ->filterColumn('parent_code', function ($query, $keyword) {
                $query->where(fn ($products) => $products->where('code', 'like', "%{$keyword}%")
                    ->orWhereHas('parentProduct', fn ($parent) => $parent->where('code', 'like', "%{$keyword}%")));
            })
            ->addColumn('unit_label', fn (Product $product) => $product->unit?->symbol ?: $product->unit?->name)
            ->escapeColumns([])
            ->toJson();
    }
}
