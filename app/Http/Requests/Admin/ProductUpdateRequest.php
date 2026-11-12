<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.update') ?? false;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        $productId = is_object($product)
            ? $product->id
            : $product;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->ignore($productId),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'unit_id' => [
                'nullable',
                'integer',
                'exists:units,id',
            ],

            'product_type' => [
                'required',
                Rule::in(['stock', 'service']),
            ],

            'garment_item_type' => ['sometimes', 'required', Rule::in(['general', 'fabric', 'accessory', 'work_in_process', 'finished_good', 'packaging'])],
            'style_code' => ['nullable', 'string', 'max:80', 'exists:styles,code'],
            'color' => ['nullable', 'string', 'max:80'],
            'size' => ['nullable', 'string', 'max:40'],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'min_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'max_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'reorder_point' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'track_stock' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'is_available' => ['required', 'boolean'],

            'primary_image_id' => [
                'nullable',
                'integer',
                Rule::in($product->images()->pluck('id')->all()),
            ],

            'deleted_image_ids' => [
                'nullable',
                'array',
            ],

            'deleted_image_ids.*' => [
                'integer',
                Rule::in($product->images()->pluck('id')->all()),
            ],

            'images' => [
                'nullable',
                'array',
            ],

            'images.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],



        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'kode barang',
            'name' => 'nama barang',
            'category_id' => 'kategori',
            'unit_id' => 'satuan',
            'product_type' => 'tipe barang',
            'purchase_price' => 'harga beli',
            'min_stock' => 'minimum stok',
            'max_stock' => 'maximum stok',
            'reorder_point' => 'reorder point',
            'images' => __('ui.workflow.image_field'),
            'images.*' => __('ui.workflow.image_field'),
        ];
    }
}
