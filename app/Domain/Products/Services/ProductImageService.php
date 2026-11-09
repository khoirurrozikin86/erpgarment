<?php

namespace App\Domain\Products\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    /**
     * Upload multiple product images.
     */
    public function upload(
        Product $product,
        array $files
    ): void {
        if (empty($files)) {
            return;
        }

        DB::transaction(function () use ($product, $files) {

            /*
             * Jika product belum mempunyai primary image,
             * gambar pertama yang diupload akan menjadi primary.
             */
            $hasPrimary = $product->images()
                ->where('is_primary', true)
                ->exists();

            /*
             * Ambil sort order terakhir.
             */
            $sortOrder = (int) (
                $product->images()->max('sort_order') ?? -1
            );

            foreach ($files as $index => $file) {

                if (!$file instanceof UploadedFile) {
                    continue;
                }

                if (!$file->isValid()) {
                    continue;
                }

                $path = $file->store(
                    'products',
                    'public'
                );

                $sortOrder++;

                $isPrimary = !$hasPrimary && $index === 0;

                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'is_primary' => $isPrimary,
                    'sort_order' => $sortOrder,
                ]);

                if ($isPrimary) {
                    $hasPrimary = true;
                }
            }
        });
    }

    /**
     * Set a specific image as the primary image.
     */
    public function setPrimary(Product $product, ?int $imageId): void
    {
        if ($imageId === null) {
            return;
        }

        DB::transaction(function () use ($product, $imageId) {
            $image = $product->images()
                ->where('id', $imageId)
                ->first();

            if (!$image) {
                return;
            }

            $product->images()->update([
                'is_primary' => false,
            ]);

            $image->update([
                'is_primary' => true,
            ]);
        });
    }

    /**
     * Delete selected images for a product.
     */
    public function deleteSelected(Product $product, array $imageIds): void
    {
        if (empty($imageIds)) {
            return;
        }

        $ids = array_map('intval', $imageIds);
        $ids = array_values(array_unique(array_filter($ids, fn($id) => $id > 0)));

        if (empty($ids)) {
            return;
        }

        DB::transaction(function () use ($product, $ids) {
            $images = $product->images()
                ->whereIn('id', $ids)
                ->get();

            foreach ($images as $image) {
                if ($image->path) {
                    Storage::disk('public')->delete($image->path);
                }

                $image->delete();
            }

            $remainingPrimary = $product->images()->first();

            if ($remainingPrimary && !$product->images()->where('is_primary', true)->exists()) {
                $remainingPrimary->update(['is_primary' => true]);
            }
        });
    }

    /**
     * Delete all images belonging to product.
     */
    public function deleteAll(Product $product): void
    {
        $images = $product->images()->get();

        foreach ($images as $image) {

            if ($image->path) {
                Storage::disk('public')->delete(
                    $image->path
                );
            }

            $image->delete();
        }
    }
}
