<?php

namespace App\Domain\Pos\Actions;

use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Companies\Services\CompanyContext;
use App\Models\CashBankAccount;
use App\Models\CashBankTransaction;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidPosSaleAction
{
    public function __construct(
        private CompanyContext $company,
        private AutomaticJournalService $journals,
        private AuditLogService $auditLog,
    ) {}

    public function __invoke(PosSale $sale, string $reason, int $userId): PosSale
    {
        return DB::transaction(function () use ($sale, $reason, $userId) {
            $sale = PosSale::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($sale->id);
            if ($sale->status !== 'completed') {
                throw ValidationException::withMessages(['sale' => 'Hanya transaksi POS selesai yang dapat di-void.']);
            }

            $session = PosSession::query()->where('company_id', $this->company->id())->lockForUpdate()->findOrFail($sale->pos_session_id);
            if ($session->status !== 'open') {
                throw ValidationException::withMessages(['sale' => 'Transaksi tidak dapat di-void setelah sesi kasir ditutup.']);
            }
            if ((int) $sale->cashier_id !== $userId) {
                throw ValidationException::withMessages(['sale' => 'Kasir hanya dapat void transaksi miliknya sendiri.']);
            }

            $originalPayment = CashBankTransaction::query()
                ->where('company_id', $this->company->id())
                ->where('pos_sale_id', $sale->id)
                ->lockForUpdate()
                ->first();
            if (!$originalPayment || round((float) $originalPayment->amount, 2) !== round((float) $sale->total_amount, 2)) {
                throw ValidationException::withMessages(['sale' => 'Transaksi pembayaran POS asli tidak ditemukan atau nilainya tidak sesuai.']);
            }

            $cashAccount = CashBankAccount::query()
                ->where('company_id', $this->company->id())
                ->lockForUpdate()
                ->find($originalPayment->cash_bank_account_id);
            if (!$cashAccount) {
                throw ValidationException::withMessages(['sale' => 'Akun kas/bank pembayaran asli tidak ditemukan.']);
            }

            $movements = StockMovement::query()
                ->where('pos_sale_id', $sale->id)
                ->where('movement_type', 'pos_sale')
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get();
            $productIds = $movements->pluck('product_id')->unique()->sort()->values();
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $stocks = ProductStock::query()->whereIn('product_id', $productIds)->orderBy('product_id')->lockForUpdate()->get()->keyBy('product_id');

            $saleItems = $sale->items()->lockForUpdate()->get()->keyBy('id');
            $oldValues = $sale->load('items')->toArray();
            foreach ($movements as $originalMovement) {
                $product = $products->get($originalMovement->product_id);
                $stock = $stocks->get($originalMovement->product_id);
                $saleItem = $saleItems->get($originalMovement->pos_sale_item_id);
                $soldQuantity = abs((float) $originalMovement->quantity);
                if (!$product || !$stock || !$saleItem || (float) $originalMovement->quantity >= 0 || $soldQuantity <= 0) {
                    throw ValidationException::withMessages(['sale' => 'Data movement stok transaksi asli tidak lengkap; void dibatalkan.']);
                }

                $before = round((float) $stock->quantity, 4);
                $after = round($before + $soldQuantity, 4);
                $costAmount = round((float) $originalMovement->cost_amount, 2);
                $returnedUnitCost = $costAmount / $soldQuantity;
                $existingUnitCost = (float) $stock->average_unit_cost > 0
                    ? (float) $stock->average_unit_cost
                    : ($before > 0 && (float) $product->purchase_price > 0
                        ? (float) $product->purchase_price
                        : $returnedUnitCost);
                $newAverageCost = $after > 0
                    ? round((($before * $existingUnitCost) + $costAmount) / $after, 6)
                    : $existingUnitCost;
                $stock->update(['quantity' => $after, 'average_unit_cost' => $newAverageCost]);

                $voidMovement = StockMovement::create([
                    'product_id' => $product->id,
                    'pos_sale_id' => $sale->id,
                    'pos_sale_item_id' => $saleItem->id,
                    'created_by' => $userId,
                    'movement_type' => 'pos_void',
                    'quantity' => $soldQuantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'cost_amount' => $costAmount,
                    'notes' => "Pembatalan stok dari POS Void {$sale->number}",
                ]);
                $this->journals->recordStockMovement($voidMovement, $userId);
            }

            $sale->update([
                'status' => 'voided',
                'voided_by' => $userId,
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            $refund = CashBankTransaction::create([
                'company_id' => $this->company->id(),
                'cash_bank_account_id' => $cashAccount->id,
                'pos_session_id' => $session->id,
                'pos_sale_void_id' => $sale->id,
                'transaction_date' => today(),
                'direction' => 'out',
                'amount' => $sale->total_amount,
                'description' => "Refund void POS {$sale->number}",
                'reference_number' => $sale->number,
                'created_by' => $userId,
            ]);

            $this->journals->reversePosSale($sale, $userId);
            $this->auditLog->log(
                action: 'VOID',
                module: 'POS_SALE',
                description: "Void transaksi POS {$sale->number}: {$reason}",
                model: $sale,
                oldValues: $oldValues,
                newValues: ['sale' => $sale->fresh()->toArray(), 'refund' => $refund->toArray(), 'stock_movements' => $movements->pluck('id')],
            );

            return $sale->fresh(['session', 'cashier', 'voider', 'items.product.unit']);
        });
    }
}
