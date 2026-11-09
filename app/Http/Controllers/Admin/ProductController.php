<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Products\Queries\ProductTableQuery;
use App\Domain\Products\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductStoreRequest;
use App\Http\Requests\Admin\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Product;
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
            Excel::import(new ProductsImport(), $request->file('file'));
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
            return back()->withErrors(['file' => 'Import gagal. Periksa format file dan pastikan kode, barcode, serta SKU tidak duplikat.']);
        }

        return redirect()->route('super.products.index')->with('import_success', 'Data barang berhasil diimpor.');
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

            ->addColumn('current_stock', function (Product $product) {
                if (!$product->track_stock) {
                    return '—';
                }

                return number_format((float) ($product->stock?->quantity ?? 0), 4, ',', '.');
            })

            ->addColumn('product_type_label', function (Product $product) {
                return match ($product->product_type) {
                    'stock' => '<span class="badge bg-primary">Stock</span>',
                    'service' => '<span class="badge bg-info">Service</span>',
                    default => '<span class="badge bg-secondary">'
                        . e($product->product_type)
                        . '</span>',
                };
            })

            ->addColumn('status', function (Product $product) {
                return $product->is_active
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Nonaktif</span>';
            })

            ->addColumn('image', function (Product $product) {
                if (!$product->primaryImage) {
                    return '<div class="text-center">
                                <span class="text-muted">
                                    <i class="fas fa-image"></i>
                                </span>
                            </div>';
                }

                $url = asset('storage/' . $product->primaryImage->path);

                return '<div class="text-center">
                            <img src="' . e($url) . '"
                                alt="' . e($product->name) . '"
                                style="
                                    width:45px;
                                    height:45px;
                                    object-fit:cover;
                                    border-radius:6px;
                                ">
                        </div>';
            })

            ->addColumn('purchase_price_formatted', function (Product $product) {
                return 'Rp ' . number_format(
                    (float) $product->purchase_price,
                    0,
                    ',',
                    '.'
                );
            })

            ->addColumn('sales_price_formatted', function (Product $product) {
                return 'Rp ' . number_format(
                    (float) $product->sales_price,
                    0,
                    ',',
                    '.'
                );
            })

            ->addColumn('actions', function (Product $product) {
                $actions = [
                    [
                        'type' => 'edit',
                        'label' => 'Edit',
                        'icon' => 'edit-2',
                        'update_url' => route(
                            'super.products.update',
                            $product->getRouteKey()
                        ),
                        'payload' => [
                            'id' => $product->id,
                            'code' => $product->code,
                            'barcode' => $product->barcode,
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'short_name' => $product->short_name,
                            'category_id' => $product->category_id,
                            'unit_id' => $product->unit_id,
                            'product_type' => $product->product_type,
                            'purchase_price' => $product->purchase_price,
                            'sales_price' => $product->sales_price,
                            'min_stock' => $product->min_stock,
                            'max_stock' => $product->max_stock,
                            'reorder_point' => $product->reorder_point,
                            'track_stock' => (bool) $product->track_stock,
                            'taxable' => (bool) $product->taxable,
                            'tax_rate' => $product->tax_rate,
                            'allow_discount' => (bool) $product->allow_discount,
                            'allow_purchase' => (bool) $product->allow_purchase,
                            'allow_sales' => (bool) $product->allow_sales,
                            'description' => $product->description,
                            'is_active' => (bool) $product->is_active,
                            'images' => $product->images
                                ->map(function ($image) {
                                    return [
                                        'id' => $image->id,
                                        'path' => asset('storage/' . $image->path),
                                        'file_name' => $image->file_name,
                                        'is_primary' => (bool) $image->is_primary,
                                    ];
                                })
                                ->values()
                                ->all(),
                        ],
                    ],
                    [
                        'type' => 'delete',
                        'label' => 'Delete',
                        'icon' => 'trash-2',
                        'url' => route(
                            'super.products.destroy',
                            $product->getRouteKey()
                        ),
                        'confirm' => "Hapus Barang {$product->name}?",
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

            ->rawColumns([
                'image',
                'product_type_label',
                'status',
                'actions',
            ])

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
                'message' => 'Barang berhasil ditambahkan.',
                'data' => $product,
            ]);
        }

        return redirect()
            ->route('super.products.index')
            ->with(
                'success',
                'Barang berhasil ditambahkan.'
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
                'message' => 'Barang berhasil diperbarui.',
                'data' => $product,
            ]);
        }

        return redirect()
            ->route('super.products.index')
            ->with(
                'success',
                'Barang berhasil diperbarui.'
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
                'message' => 'Barang berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('super.products.index')
            ->with(
                'success',
                'Barang berhasil dihapus.'
            );
    }
}
