<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Products\Queries\ProductTableQuery;
use App\Domain\Products\Services\ProductService;
use App\Domain\Products\Services\ProductVariantService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\VariantDimension;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsTemplateExport;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Domain\Products\Services\ProductImageService;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $service,
        protected ProductImageService $imageService

    ) {}

    public function index()
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $units = Unit::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'symbol']);

        $parentProducts = Product::whereNull('parent_product_id')->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        return view('super.products.index', compact(
            'parentProducts',
            'categories',
            'units',
        ) + [
            'variantColors' => VariantDimension::options('color')->pluck('code'),
            'variantSizes' => VariantDimension::options('size')->pluck('code'),
        ]);
    }

    public function export()
    {
        return Excel::download(new ProductsExport(), 'barang.xlsx');
    }

    public function template()
    {
        $units = Unit::where('is_active', true)->orderBy('name')->orderBy('code')->get(['code', 'name']);

        $categories = Category::where('is_active', true)->orderBy('name')->orderBy('code')->get(['code', 'name']);

        return Excel::download(new ProductsTemplateExport($units, $categories), 'template-barang.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            Excel::import(new ProductsImport($this->service), $request->file('file'));
        } catch (ExcelValidationException $exception) {
            $messages = collect($exception->failures())
                ->flatMap(fn($failure) => $failure->errors())
                ->take(10)
                ->implode(' ');

            return back()->withErrors(['file' => $messages]);
        } catch (ValidationException $exception) {
            $messages = collect($exception->errors())
                ->flatten()
                ->take(10)
                ->implode(' ');

            return back()->withErrors(['file' => $messages]);
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['file' => 'Import gagal. Periksa format file dan pastikan kode barang tidak duplikat.']);
        }

        return redirect()->route('super.products.index')->with('import_success', 'Data barang berhasil diimpor.');
    }

    public function variants(Product $product): JsonResponse
    {
        abort_if($product->parent_product_id, 404);
        $rows = Product::where(fn ($q) => $q->whereKey($product->id)->orWhere('parent_product_id', $product->id))
            ->with(['stock', 'unit'])->orderBy('color')->orderBy('size')->get();
        return response()->json([
            'label' => $product->code.' — '.$product->name,
            'create_url' => auth()->user()->can('products.create') && $product->is_active && $product->product_type === 'stock'
                ? route('super.products.variants.store', $product) : null,
            'total' => $rows->sum(fn ($row) => (float) ($row->stock?->quantity ?? 0)),
            'items' => $rows->map(fn ($row) => [
                'code' => $product->code, 'sku_code' => $row->parent_product_id ? $row->code : null, 'color' => $row->color ?: '—', 'size' => $row->size ?: '—',
                'quantity' => (float) ($row->stock?->quantity ?? 0),
                'unit' => $row->unit?->symbol ?: $row->unit?->name,
                'stock_card_url' => auth()->user()->can('stock-card.view') ? route('super.stock-card.index', ['product_id' => $row->id]) : null,
            ]),
        ]);
    }

    public function storeVariant(Request $request, Product $product, ProductVariantService $variants): JsonResponse
    {
        abort_if($product->parent_product_id, 404);
        $data = $request->validate([
            'color' => ['required_without:size', 'nullable', 'string', 'max:100'],
            'size' => ['required_without:color', 'nullable', 'string', 'max:100'],
        ]);

        $color = filled($data['color'] ?? null) ? VariantDimension::resolve('color', $data['color'], 'color', 1) : '';
        $size = filled($data['size'] ?? null) ? VariantDimension::resolve('size', $data['size'], 'size', 1) : null;
        $variant = DB::transaction(fn () => $variants->resolve($product->id, $color, $size));

        return response()->json(['code' => $variant->code]);
    }

    public function dt(ProductTableQuery $query): JsonResponse
    {
        return DataTables::eloquent($query->builder())
            ->addColumn('category_name', function (Product $product) {
                return $product->category?->name ?? '-';
            })

            ->addColumn('unit_name', function (Product $product) {
                if (!$product->unit) {
                    return '-';
                }

                return $product->unit->name
                    . ($product->unit->symbol
                        ? " ({$product->unit->symbol})"
                        : '');
            })

            ->addColumn('global_stock', fn (Product $product) => number_format((float) ($product->stock?->quantity ?? 0) + $product->variants->sum(fn ($v) => (float) ($v->stock?->quantity ?? 0)), 4, ',', '.'))
            ->addColumn('variants_button', fn (Product $product) => '<button type="button" class="btn btn-sm btn-outline-primary view-item-variants" data-url="'.e(route('super.products.variants', $product)).'">'.e(__('variants.variant')).' ('.$product->variants->count().')</button>')

            ->addColumn('item_status', fn (Product $product) => $product->is_active
                ? '<span class="badge bg-success">'.e(__('ui.common.active')).'</span>'
                : '<span class="badge bg-secondary">'.e(__('ui.common.inactive')).'</span>')

            ->addColumn('actions', function (Product $product) {
                $actions = [
                    [
                        'type' => 'edit',
                        'label' => __('ui.common.edit'),
                        'icon' => 'edit-2',
                        'update_url' => route(
                            'super.products.update',
                            $product->getRouteKey()
                        ),
                        'payload' => [
                            'id' => $product->id,
                            'code' => $product->code,
                            'name' => $product->name,
                            'category_id' => $product->category_id,
                            'unit_id' => $product->unit_id,
                            'product_type' => $product->product_type,
                            'garment_item_type' => $product->garment_item_type,
                            'parent_product_id' => $product->parent_product_id,
                            'color' => $product->color,
                            'size' => $product->size,
                            'purchase_price' => $product->purchase_price,
                            'min_stock' => $product->min_stock,
                            'max_stock' => $product->max_stock,
                            'reorder_point' => $product->reorder_point,
                            'track_stock' => (bool) $product->track_stock,
                            'is_active' => (bool) $product->is_active,
                            'is_available' => (bool) $product->is_available,
                        ],
                    ],
                    [
                        'type' => 'delete',
                        'label' => __('ui.common.delete'),
                        'icon' => 'trash-2',
                        'url' => route(
                            'super.products.destroy',
                            $product->getRouteKey()
                        ),
                        'confirm' => __('ui.workflow.product_delete_confirmation', ['name' => $product->name]),
                        'payload' => [
                            'id' => $product->id,
                            'name' => $product->name,
                        ],
                        'disabled' => false,
                    ],
                ];

                return view(
                    'admin.partials.table-actions',
                    compact('actions')
                )->render();
            })

            ->rawColumns(['item_status', 'actions', 'variants_button'])

            ->toJson();
    }

    public function store(
        ProductStoreRequest $request
    ): JsonResponse|Response {

        $validated = $request->validated();

        /*
     * Ambil file gambar.
     */
        $images = $request->file('images', []);

        /*
     * Jangan kirim images ke ProductService.
     */
        unset($validated['images']);

        /*
     * Simpan Product.
     */
        $product = $this->service->create(
            $validated
        );

        /*
     * Simpan Product Images.
     */
        $this->imageService->upload(
            $product,
            $images
        );

        /*
     * Reload relasi.
     */
        $product->load([
            'images',
            'primaryImage',
        ]);

        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => __('ui.workflow.product_added_message'),
                'data' => $product,
            ]);
        }

        return redirect()
            ->route('super.products.index')
            ->with(
                'success',
                __('ui.workflow.product_added_message')
            );
    }

    public function update(
        ProductUpdateRequest $request,
        Product $product
    ): JsonResponse|Response {

        $validated = $request->validated();

        /*
     * Ambil gambar baru.
     */
        $images = $request->file('images', []);

        /*
     * Jangan kirim file ke ProductService.
     */
        unset($validated['images']);
        $validated['style_code'] = $product->style_code;

        /*
     * Update data Product.
     */
        $product = $this->service->update(
            $product,
            $validated
        );

        /*
     * Jika ada gambar baru,
     * tambahkan ke product_images.
     */
        $this->imageService->upload(
            $product,
            $images
        );

        $deletedImageIds = $request->input('deleted_image_ids', []);
        $this->imageService->deleteSelected(
            $product,
            $deletedImageIds
        );

        $primaryImageId = $request->input('primary_image_id');

        if ($primaryImageId !== null && $primaryImageId !== '') {
            $this->imageService->setPrimary(
                $product,
                (int) $primaryImageId
            );
        }

        /*
     * Reload.
     */
        $product->load([
            'images',
            'primaryImage',
        ]);

        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => __('ui.workflow.product_updated_message'),
                'data' => $product,
            ]);
        }

        return redirect()
            ->route('super.products.index')
            ->with(
                'success',
                __('ui.workflow.product_updated_message')
            );
    }

    public function destroy(
        Request $request,
        Product $product
    ): JsonResponse|Response {

        /*
     * Hapus file gambar terlebih dahulu.
     */
        $this->imageService->deleteAll(
            $product
        );

        /*
     * Hapus Product.
     */
        $this->service->delete(
            $product
        );

        if ($request->expectsJson()) {

            return response()->json([
                'success' => true,
                'message' => __('ui.workflow.product_delete_success'),
            ]);
        }

        return redirect()
            ->route('super.products.index')
            ->with(
                'success',
                __('ui.workflow.product_delete_success')
            );
    }
}
