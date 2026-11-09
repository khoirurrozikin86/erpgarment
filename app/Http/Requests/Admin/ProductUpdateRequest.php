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

            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'barcode')->ignore($productId),
            ],

            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'short_name' => [
                'nullable',
                'string',
                'max:100',
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

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'sales_price' => [
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

            'taxable' => [
                'nullable',
                'boolean',
            ],

            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'allow_discount' => [
                'nullable',
                'boolean',
            ],

            'allow_purchase' => [
                'nullable',
                'boolean',
            ],

            'allow_sales' => [
                'nullable',
                'boolean',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

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
            'barcode' => 'barcode',
            'sku' => 'SKU',
            'name' => 'nama barang',
            'short_name' => 'nama singkat',
            'category_id' => 'kategori',
            'unit_id' => 'satuan',
            'product_type' => 'tipe barang',
            'purchase_price' => 'harga beli',
            'sales_price' => 'harga jual',
            'min_stock' => 'minimum stok',
            'max_stock' => 'maximum stok',
            'reorder_point' => 'reorder point',
            'tax_rate' => 'tax rate',
        ];
    }
}
