<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Sales\DTOs\SalesQuotationData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesQuotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSalesQuotationAction
{
    public function __construct(private DocumentNumberService $numbers, private AuditLogService $auditLog) {}

    public function __invoke(SalesQuotationData $data, int $userId): SalesQuotation
    {
        return DB::transaction(function () use ($data, $userId) {
            $attributes = $data->attributes;
            $customer = Customer::query()->where('is_active', true)->find($attributes['customer_id']);
            if (!$customer) {
                throw ValidationException::withMessages(['customer_id' => 'Customer tidak aktif atau tidak ditemukan.']);
            }

            $items = collect($attributes['items'])->keyBy(fn ($item) => (int) $item['product_id']);
            $products = Product::query()->whereIn('id', $items->keys())->where('allow_sales', true)->where('is_active', true)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== $items->count()) {
                throw ValidationException::withMessages(['items' => 'Semua barang harus aktif untuk penjualan.']);
            }

            $quotation = SalesQuotation::create([
                'number' => $this->numbers->generate('sales_quotation'),
                'customer_id' => $customer->id,
                'created_by' => $userId,
                'quote_date' => $attributes['quote_date'],
                'valid_until' => $attributes['valid_until'] ?? null,
                'status' => 'draft',
                'notes' => $attributes['notes'] ?? null,
            ]);

            $subtotal = 0;
            $taxAmount = 0;
            foreach ($items as $productId => $item) {
                $product = $products->get($productId);
                $quantity = round((float) $item['quantity'], 4);
                $unitPrice = round((float) $item['unit_price'], 2);
                $gross = round($quantity * $unitPrice, 2);
                $discount = round((float) ($item['discount_amount'] ?? 0), 2);
                if ($discount > $gross) {
                    throw ValidationException::withMessages(['items' => "Diskon {$product->name} tidak boleh melebihi subtotal."]);
                }
                $net = round($gross - $discount, 2);
                $taxRate = $product->taxable ? (float) $product->tax_rate : 0;
                $tax = round($net * $taxRate / 100, 2);
                $lineTotal = round($net + $tax, 2);
                $subtotal += $net;
                $taxAmount += $tax;
                $quotation->items()->create([
                    'product_id' => $product->id, 'quantity' => $quantity, 'unit_price' => $unitPrice,
                    'discount_amount' => $discount, 'tax_rate' => $taxRate, 'line_total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }
            $quotation->update(['subtotal' => round($subtotal, 2), 'tax_amount' => round($taxAmount, 2), 'total_amount' => round($subtotal + $taxAmount, 2)]);

            $this->auditLog->log(action: 'CREATE', module: 'SALES_QUOTATION', description: "Membuat quotation {$quotation->number}", model: $quotation, oldValues: null, newValues: $quotation->load('items')->toArray());
            return $quotation->load(['customer', 'creator', 'items.product.unit']);
        });
    }
}
