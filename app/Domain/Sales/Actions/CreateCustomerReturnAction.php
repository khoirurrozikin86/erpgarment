<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Sales\DTOs\CustomerReturnData;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\ChartOfAccount;
use App\Models\CustomerReturn;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCustomerReturnAction
{
    public function __construct(
        private CompanyContext $company,
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
        private AutomaticJournalService $journals,
    ) {}

    public function __invoke(CustomerReturnData $data, int $userId): CustomerReturn
    {
        return DB::transaction(function () use ($data, $userId) {
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($data->salesInvoiceId);
            if ($invoice->status !== 'issued') {
                throw ValidationException::withMessages(['sales_invoice_id' => 'Customer Return hanya dapat dibuat dari Sales Invoice yang sudah diterbitkan.']);
            }

            $invoiceItems = SalesInvoiceItem::query()
                ->where('sales_invoice_id', $invoice->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $returnItems = collect($data->items)
                ->filter(fn(array $item) => (float) $item['quantity'] > 0)
                ->values();
            if ($returnItems->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'Masukkan jumlah untuk minimal satu barang retur.']);
            }

            foreach ($returnItems as $item) {
                $invoiceItem = $invoiceItems->get((int) $item['sales_invoice_item_id']);
                if (!$invoiceItem) {
                    throw ValidationException::withMessages(['items' => 'Barang yang dipilih bukan bagian dari invoice ini.']);
                }
                $available = round((float) $invoiceItem->quantity - (float) $invoiceItem->returned_quantity, 4);
                if ((float) $item['quantity'] > $available) {
                    throw ValidationException::withMessages(['items' => "Jumlah retur melebihi sisa kuantitas yang dapat diretur pada invoice {$invoice->number}."]);
                }
            }

            $productIds = $returnItems->map(fn(array $item) => (int) $invoiceItems->get((int) $item['sales_invoice_item_id'])->product_id)->unique()->sort()->values();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $stocks = [];
            foreach ($productIds as $productId) {
                $stock = ProductStock::query()->where('product_id', $productId)->lockForUpdate()->first();
                if (!$stock) {
                    ProductStock::query()->firstOrCreate(['product_id' => $productId], ['quantity' => 0, 'average_unit_cost' => 0]);
                    $stock = ProductStock::query()->where('product_id', $productId)->lockForUpdate()->firstOrFail();
                }
                $stocks[$productId] = $stock;
            }

            $cashAccount = CashBankAccount::query()
                ->where('company_id', $this->company->id())
                ->where('is_active', true)
                ->with('chartOfAccount')
                ->lockForUpdate()
                ->find($data->cashBankAccountId);
            $returnsAccount = ChartOfAccount::query()
                ->where('company_id', $this->company->id())
                ->where('code', '4200')
                ->where('account_type', 'revenue')
                ->where('is_active', true)
                ->where('is_group', false)
                ->first();
            if (!$cashAccount || !$cashAccount->chartOfAccount || !$returnsAccount) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Pilih akun kas/bank aktif yang terhubung ke COA dan pastikan akun retur penjualan 4200 tersedia.']);
            }

            $deliveryMovements = StockMovement::query()
                ->whereIn('delivery_item_id', $invoiceItems->pluck('delivery_item_id'))
                ->where('movement_type', 'sales_delivery')
                ->get()
                ->keyBy('delivery_item_id');

            $computedItems = [];
            $subtotal = 0;
            $taxAmount = 0;
            foreach ($returnItems as $item) {
                $invoiceItem = $invoiceItems->get((int) $item['sales_invoice_item_id']);
                $product = $products->get($invoiceItem->product_id);
                if (!$product) {
                    throw ValidationException::withMessages(['items' => 'Produk pada invoice sudah tidak tersedia.']);
                }

                $quantity = round((float) $item['quantity'], 4);
                $gross = round($quantity * (float) $invoiceItem->unit_price, 2);
                $discount = (float) $invoiceItem->quantity > 0
                    ? round((float) $invoiceItem->discount_amount * $quantity / (float) $invoiceItem->quantity, 2)
                    : 0;
                $lineSubtotal = max(0, round($gross - $discount, 2));
                $lineTax = round($lineSubtotal * (float) $invoiceItem->tax_rate / 100, 2);
                $lineTotal = round($lineSubtotal + $lineTax, 2);
                $costAmount = 0;

                if ($product->track_stock) {
                    $deliveryMovement = $deliveryMovements->get($invoiceItem->delivery_item_id);
                    $soldQuantity = abs((float) ($deliveryMovement?->quantity ?? 0));
                    $unitCost = $soldQuantity > 0 ? (float) $deliveryMovement->cost_amount / $soldQuantity : 0;
                    if ($unitCost <= 0) {
                        throw ValidationException::withMessages(['items' => "Biaya pokok asal untuk {$product->name} tidak ditemukan; retur tidak dapat diposting."]);
                    }
                    $costAmount = round($quantity * $unitCost, 2);
                }

                $computedItems[] = compact('invoiceItem', 'product', 'quantity', 'gross', 'discount', 'lineSubtotal', 'lineTax', 'lineTotal', 'costAmount');
                $subtotal += $lineSubtotal;
                $taxAmount += $lineTax;
            }

            $customerReturn = CustomerReturn::create([
                'number' => $this->numbers->generate('customer_return'),
                'company_id' => $this->company->id(),
                'sales_invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'cash_bank_account_id' => $cashAccount->id,
                'returned_by' => $userId,
                'returned_at' => $data->returnedAt,
                'reason' => $data->reason,
                'notes' => $data->notes,
                'subtotal' => round($subtotal, 2),
                'tax_amount' => round($taxAmount, 2),
                'total_amount' => round($subtotal + $taxAmount, 2),
            ]);

            foreach ($computedItems as $item) {
                $returnLine = $customerReturn->items()->create([
                    'sales_invoice_item_id' => $item['invoiceItem']->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['invoiceItem']->unit_price,
                    'discount_amount' => $item['discount'],
                    'tax_rate' => $item['invoiceItem']->tax_rate,
                    'subtotal' => $item['lineSubtotal'],
                    'tax_amount' => $item['lineTax'],
                    'line_total' => $item['lineTotal'],
                    'cost_amount' => $item['costAmount'],
                ]);

                $item['invoiceItem']->update([
                    'returned_quantity' => round((float) $item['invoiceItem']->returned_quantity + $item['quantity'], 4),
                ]);

                if ($item['product']->track_stock) {
                    $stock = $stocks[$item['product']->id];
                    $before = round((float) $stock->quantity, 4);
                    $after = round($before + $item['quantity'], 4);
                    $oldUnitCost = (float) $stock->average_unit_cost > 0
                        ? (float) $stock->average_unit_cost
                        : ($before > 0 && (float) $item['product']->purchase_price > 0
                            ? (float) $item['product']->purchase_price
                            : $item['costAmount'] / $item['quantity']);
                    $newAverageCost = $after > 0
                        ? round((($before * $oldUnitCost) + $item['costAmount']) / $after, 6)
                        : $oldUnitCost;
                    $stock->update(['quantity' => $after, 'average_unit_cost' => $newAverageCost]);

                    $movement = StockMovement::create([
                        'product_id' => $item['product']->id,
                        'customer_return_id' => $customerReturn->id,
                        'customer_return_item_id' => $returnLine->id,
                        'created_by' => $userId,
                        'movement_type' => 'customer_return',
                        'quantity' => $item['quantity'],
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'cost_amount' => $item['costAmount'],
                        'notes' => "Barang kembali dari Customer Return {$customerReturn->number}",
                    ]);
                    $this->journals->recordStockMovement($movement, $userId);
                }
            }

            $refundTransaction = CashBankTransaction::create([
                'company_id' => $this->company->id(),
                'cash_bank_account_id' => $cashAccount->id,
                'counter_chart_account_id' => $returnsAccount->id,
                'customer_return_id' => $customerReturn->id,
                'transaction_date' => Carbon::parse($customerReturn->returned_at)->toDateString(),
                'direction' => 'out',
                'amount' => $customerReturn->total_amount,
                'description' => "Refund customer untuk Customer Return {$customerReturn->number}",
                'reference_number' => $customerReturn->number,
                'created_by' => $userId,
            ]);
            $this->journals->recordCustomerReturnRefund($refundTransaction, $customerReturn, $userId);

            $this->auditLog->log(
                action: 'RETURN',
                module: 'CUSTOMER_RETURN',
                description: "Mencatat Customer Return {$customerReturn->number} untuk invoice {$invoice->number}",
                model: $customerReturn,
                oldValues: null,
                newValues: $customerReturn->load('items')->toArray(),
            );

            return $customerReturn->load(['invoice', 'customer', 'cashBankAccount', 'returner', 'items.product.unit']);
        });
    }
}
