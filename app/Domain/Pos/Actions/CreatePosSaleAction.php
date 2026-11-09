<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Settings\Services\DocumentNumberService;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePosSaleAction
{
    public function __construct(
        private CompanyContext $company,
        private DocumentNumberService $numbers,
        private AuditLogService $auditLog,
        private AutomaticJournalService $journals,
    ) {}

    public function __invoke(array $data, int $userId): PosSale
    {
        return DB::transaction(function () use ($data, $userId) {
            $session = PosSession::query()->where('company_id', $this->company->id())->lockForUpdate()
                ->where('status', 'open')->where('opened_by', $userId)->find($data['pos_session_id']);
            if (!$session) throw ValidationException::withMessages(['pos_session_id' => 'Sesi POS terbuka milik Anda tidak ditemukan.']);

            $account = CashBankAccount::query()->where('company_id', $this->company->id())->where('is_active', true)
                ->lockForUpdate()->find($data['cash_bank_account_id']);
            if (!$account || !$account->chart_of_account_id) throw ValidationException::withMessages(['cash_bank_account_id' => 'Pilih akun kas/bank aktif yang sudah dipetakan ke COA.']);
            if ($data['payment_method'] === 'cash' && $account->id !== $session->cash_bank_account_id) {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Pembayaran tunai harus menggunakan rekening kas yang terhubung ke sesi aktif.']);
            }
            if ($data['payment_method'] !== 'cash' && $account->type !== 'bank') {
                throw ValidationException::withMessages(['cash_bank_account_id' => 'Pembayaran transfer/kartu harus menggunakan rekening bank.']);
            }

            $lines = collect($data['items'])->sortBy('product_id')->values();
            $products = Product::query()->whereIn('id', $lines->pluck('product_id')->unique())
                ->where('is_active', true)->where('allow_sales', true)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== $lines->pluck('product_id')->unique()->count()) {
                throw ValidationException::withMessages(['items' => 'Salah satu barang sudah tidak aktif atau tidak dapat dijual.']);
            }
            $stocks = ProductStock::query()->whereIn('product_id', $products->keys())->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');

            $subtotal = $discountTotal = $taxTotal = 0.0;
            $computed = [];
            foreach ($lines as $line) {
                $product = $products->get((int) $line['product_id']);
                $quantity = round((float) $line['quantity'], 4);
                if ($quantity <= 0) throw ValidationException::withMessages(['items' => 'Jumlah barang harus lebih dari nol.']);
                $stock = $stocks->get($product->id);
                $before = (float) ($stock?->quantity ?? 0);
                if ($product->track_stock && $before < $quantity) {
                    throw ValidationException::withMessages(['items' => "Stok {$product->code} tidak cukup. Tersedia {$before}."]);
                }
                $unitPrice = round((float) $product->sales_price, 2);
                if ($unitPrice <= 0) throw ValidationException::withMessages(['items' => "Harga jual {$product->code} belum diatur."]);
                $gross = round($quantity * $unitPrice, 2);
                $discount = round($gross * ((float) $data['discount_rate'] / 100), 2);
                if ($discount > 0 && !$product->allow_discount) throw ValidationException::withMessages(['discount_rate' => "Diskon tidak diizinkan untuk barang {$product->code}."]);
                $net = round($gross - $discount, 2);
                $taxRate = $product->taxable ? (float) $product->tax_rate : 0.0;
                $tax = round($net * $taxRate / 100, 2);
                $costPerUnit = (float) ($stock?->average_unit_cost ?: $product->purchase_price);
                if ($product->track_stock && $costPerUnit <= 0) {
                    throw ValidationException::withMessages(['items' => "Biaya persediaan {$product->code} belum tersedia. Isi harga beli atau catat penerimaan lebih dahulu."]);
                }
                $costAmount = $product->track_stock ? round($quantity * $costPerUnit, 2) : 0.0;
                $computed[] = compact('product', 'stock', 'quantity', 'before', 'unitPrice', 'discount', 'taxRate', 'tax', 'net', 'gross', 'costAmount');
                $subtotal += $gross; $discountTotal += $discount; $taxTotal += $tax;
            }
            $subtotal = round($subtotal, 2);
            $discountTotal = round($discountTotal, 2);
            $taxTotal = round($taxTotal, 2);
            $total = round($subtotal - $discountTotal + $taxTotal, 2);
            if ($total <= 0) throw ValidationException::withMessages(['items' => 'Total transaksi harus lebih dari nol.']);
            $paid = $data['payment_method'] === 'cash' ? round((float) $data['paid_amount'], 2) : $total;
            if ($paid < $total) throw ValidationException::withMessages(['paid_amount' => 'Pembayaran kurang dari total belanja.']);

            $sale = PosSale::create([
                'company_id' => $this->company->id(), 'number' => $this->numbers->generate('pos'),
                'pos_session_id' => $session->id, 'cashier_id' => $userId, 'cash_bank_account_id' => $account->id,
                'sold_at' => now(), 'subtotal' => $subtotal, 'discount_rate' => $data['discount_rate'],
                'discount_amount' => $discountTotal, 'tax_amount' => $taxTotal, 'total_amount' => $total,
                'payment_method' => $data['payment_method'], 'paid_amount' => $paid,
                'change_amount' => round($paid - $total, 2), 'status' => 'completed',
            ]);

            foreach ($computed as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $item = PosSaleItem::create([
                    'pos_sale_id' => $sale->id, 'product_id' => $product->id, 'quantity' => $line['quantity'],
                    'unit_price' => $line['unitPrice'], 'discount_amount' => $line['discount'],
                    'tax_rate' => $line['taxRate'], 'tax_amount' => $line['tax'],
                    'line_total' => round($line['net'] + $line['tax'], 2), 'cost_amount' => $line['costAmount'],
                ]);
                if (!$product->track_stock) continue;
                $stock = $line['stock'];
                $after = round($line['before'] - $line['quantity'], 4);
                $stock->update(['quantity' => $after]);
                $movement = StockMovement::create([
                    'product_id' => $product->id, 'pos_sale_id' => $sale->id, 'pos_sale_item_id' => $item->id,
                    'created_by' => $userId, 'movement_type' => 'pos_sale', 'quantity' => -$line['quantity'],
                    'quantity_before' => $line['before'], 'quantity_after' => $after,
                    'cost_amount' => $line['costAmount'], 'notes' => "Penjualan POS {$sale->number}",
                ]);
                $this->journals->recordStockMovement($movement, $userId);
            }

            CashBankTransaction::create([
                'company_id' => $this->company->id(), 'cash_bank_account_id' => $account->id,
                'pos_session_id' => $session->id, 'pos_sale_id' => $sale->id,
                'transaction_date' => today(), 'direction' => 'in', 'amount' => $total,
                'description' => "Penjualan POS {$sale->number}", 'created_by' => $userId,
            ]);
            $this->journals->recordPosSale($sale, $userId);
            $this->auditLog->log(action: 'CREATE', module: 'POS_SALE', description: "Mencatat penjualan POS {$sale->number}", model: $sale, oldValues: null, newValues: $sale->load('items.product')->toArray());
            return $sale->load(['items.product.unit', 'cashier', 'cashBankAccount']);
        });
    }
}
