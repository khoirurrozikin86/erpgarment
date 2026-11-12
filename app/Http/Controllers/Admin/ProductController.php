<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Products\Queries\ProductTableQuery;
use App\Domain\Products\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Style;
use App\Models\Unit;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsTemplateExport;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        return view('super.products.index', compact(
            'categories',
            'units'
        ));
    }

    public function export()
    {
        return Excel::download(new ProductsExport(), 'barang.xlsx');
    }

    public function template()
    {
        return Excel::download(new ProductsTemplateExport(), 'template-barang.xlsx');
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

    public function styleOptions(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        if ($search === '') {
            return response()->json(['data' => []]);
        }

        $styles = Style::query()
            ->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            })
            ->orderBy('code')
            ->limit(20)
            ->get(['code', 'name']);

        return response()->json(['data' => $styles]);
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

            ->addColumn('availability', fn (Product $product) => $product->is_available
                ? '<span class="badge bg-success">Masih Ada</span>'
                : '<span class="badge bg-secondary">Tidak Ada</span>')

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
                            'style_code' => $product->style_code,
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

            ->rawColumns(['availability', 'actions'])

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
