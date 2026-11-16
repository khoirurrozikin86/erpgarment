<?php

namespace Database\Seeders;

use App\Domain\Accounting\Actions as Accounting;
use App\Domain\Accounting\DTOs as AccountingData;
use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Inventory\Actions as Inventory;
use App\Domain\Manufacturing\Actions as Production;
use App\Domain\Purchasing\Actions as Purchasing;
use App\Domain\Purchasing\DTOs as PurchasingData;
use App\Domain\Subcontract\Actions as Subcontract;
use App\Models\{AuditLog, BillOfMaterial, CashBankAccount, ChartOfAccount, Company, Customer, Exporter, GoodsReceipt, GoodsSentNote, Importer, MaterialIssueDocument, Product, ProductStock, ProductionOrder, ProductionReturnDocument, SubcontractContract, Subcontractor, Supplier, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{Auth, DB};
use RuntimeException;

/** Additive upgrade of the original simulation; safe to call after its V1 marker exists. */
class SimulationModulesSeeder extends Seeder
{
    private int $company;
    private int $actor;
    private int $reviewer;
    private array $products;
    private Supplier $supplier;
    private Customer $customer;
    private array $chains = [];

    public function run(): void
    {
        $context = app(CompanyContext::class);
        $previousCompany = $context->has() ? $context->get() : null;
        $previousUser = Auth::user();
        $company = Company::where('code', 'SD001')->where('is_active', true)->first()
            ?? Company::where('is_active', true)->firstOrFail();
        $this->company = $company->id;
        $marker = 'SIMULATION-V2-COMPANY-'.$this->company;
        try {
            $context->set($company);
            DB::transaction(function () use ($company, $marker) {
                Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
                if (AuditLog::where('module', 'SIMULATION')->where('description', $marker)->exists()) {
                    $this->command?->info('Simulasi semua modul V2 sudah tersedia; transaksi tidak digandakan.');
                    return;
                }
                $this->actor = User::where('email', 'sim.operator@localhost.test')->firstOrFail()->id;
                $this->reviewer = User::where('email', 'sim.reviewer@localhost.test')->firstOrFail()->id;
                Auth::setUser(User::findOrFail($this->actor));
                $this->products = Product::where('code', 'like', 'SIM-%')->get()->keyBy('code')->all();
                $this->supplier = Supplier::where('code', 'SIM-SUPPLIER')->firstOrFail();
                $this->customer = Customer::where('code', 'SIM-CUSTOMER')->firstOrFail();
                if (ProductionOrder::where('number', 'like', 'SIM2-MO-%')->exists()) {
                    throw new RuntimeException('SIM2-MO sudah ada tanpa marker V2. Data tidak diubah.');
                }
                $this->repairOriginalReferences();
                foreach (['23', '262', '27', '40', '20'] as $i => $type) {
                    $this->command?->info('Membuat rantai pembelian → produksi → pengiriman '.($i + 1).'/5...');
                    $this->chains[] = $this->chain($i + 1, $type);
                }
                $this->warehouseAndInventory();
                $this->subcontract();
                $this->accounting();
                $this->validateBalances();
                AuditLog::create(['user_id' => $this->actor, 'action' => 'SEED', 'module' => 'SIMULATION',
                    'description' => $marker, 'new_values' => ['company_id' => $this->company,
                        'orders' => collect($this->chains)->pluck('order.id')->all(), 'date' => now()->toDateString()]]);
                $this->command?->info('Simulasi V2 selesai: lima rantai transaksi berelasi, retur disetujui, opname, pembayaran, dan laporan.');
            });
        } finally {
            $previousCompany ? $context->set($previousCompany) : $context->clear();
            $previousUser ? Auth::setUser($previousUser) : Auth::forgetUser();
        }
    }

    private function product(string $key): Product
    {
        return $this->products['SIM-'.$key] ?? throw new RuntimeException('Jalankan SimulationSeeder V1 terlebih dahulu.');
    }

    private function date(int $days = 0): string
    {
        return now()->subDays(min($days, now()->day - 1))->toDateString();
    }

    private function repairOriginalReferences(): void
    {
        // Incoming report filters this metadata, not the standalone BC table.
        $receipt = GoodsReceipt::where('received_by', $this->actor)->where('status', 'approved')
            ->where('notes', 'SIM-GRN disetujui')->firstOrFail();
        $old = $receipt->only(['customs_document_code', 'customs_document_number', 'customs_document_date']);
        $receipt->update(['customs_document_code' => 'BC 2.3', 'customs_document_number' => 'SIM-DOC-23',
            'customs_document_date' => $receipt->received_at->toDateString()]);
        app(\App\Domain\Audit\Services\AuditLogService::class)->log('UPDATE', 'SIMULATION',
            'SIM-Melengkapi referensi BC GRN untuk laporan pemasukan', $receipt, $old, $receipt->fresh()->toArray(), $this->actor);
    }

    private function chain(int $index, string $incomingType): array
    {
        $date = $this->date(6 - $index);
        $order = app(Production\CreateProductionOrderAction::class)([
            'number' => 'SIM2-MO-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT), 'buyer_po_number' => 'SIM2-BUYER-'.$index,
            'currency' => 'IDR', 'po_date' => $date, 'due_date' => now()->addDays(14)->toDateString(),
            'customer_id' => $this->customer->id, 'output_product_id' => $this->product('POLO')->id,
            'planned_quantity' => 25, 'price_per_unit' => 100000, 'description' => 'SIM2-Rantai '.$index,
        ], $this->actor);
        $mr = app(Purchasing\CreateMaterialRequestAction::class)(PurchasingData\MaterialRequestData::fromArray([
            'department' => 'SIM2-PPIC', 'reason' => 'SIM2-Pengadaan untuk '.$order->number,
            'production_order_id' => $order->id, 'bill_of_material_id' => $order->bill_of_material_id,
            'output_product_id' => $order->output_product_id, 'bom_version' => $order->bom_version, 'production_quantity' => 25,
            'items' => $order->materials->map(fn ($item) => ['product_id' => $item->product_id, 'quantity' => 80])->all(),
        ]), $this->actor);
        app(Purchasing\ReviewMaterialRequestAction::class)($mr, new PurchasingData\MaterialRequestReviewData($this->reviewer, 'approved', 'SIM2-Persetujuan pembelian'));
        $pr = app(Purchasing\CreatePurchaseRequestAction::class)(PurchasingData\PurchaseRequestData::fromArray([
            'material_request_id' => $mr->id, 'supplier_id' => $this->supplier->id, 'notes' => 'SIM2-PR '.$order->number,
            'items' => $mr->items->map(fn ($item) => ['material_request_item_id' => $item->id, 'quantity' => 80,
                'unit_price' => (float) $item->product->purchase_price])->all(),
        ]), $this->actor);
        app(Purchasing\ReviewPurchaseRequestAction::class)($pr, new PurchasingData\PurchaseRequestReviewData($this->reviewer, 'approved', 'SIM2-Disetujui'));
        $po = app(Purchasing\CreatePurchaseOrderAction::class)(PurchasingData\PurchaseOrderData::fromArray([
            'purchase_request_id' => $pr->id, 'notes' => 'SIM2-PO '.$order->number, 'expected_delivery_at' => $date,
        ]), $this->actor);
        app(Purchasing\IssuePurchaseOrderAction::class)($po);
        $code = match ($incomingType) {'23' => 'BC 2.3', '262' => 'BC 2.6.2', '27' => 'BC 2.7', '40' => 'BC 4.0', '20' => 'BC 2.0'};
        $receipt = app(Purchasing\CreateGoodsReceiptAction::class)(PurchasingData\GoodsReceiptData::fromArray([
            'purchase_order_id' => $po->id, 'received_at' => $date, 'notes' => 'SIM2-GRN '.$order->number,
            'customs_document_code' => $code, 'customs_document_number' => 'SIM2-DOC-'.$incomingType,
            'customs_document_date' => $date, 'invoice_number' => 'SIM2-INV-'.$index, 'invoice_date' => $date, 'currency' => 'IDR',
            'items' => $po->items->map(fn ($item) => ['purchase_order_item_id' => $item->id, 'quantity' => 60,
                'lot_number' => 'SIM2-'.$index.'-'.$item->product_id])->all(),
        ]), $this->actor);
        app(Purchasing\ApproveGoodsReceiptAction::class)($receipt, $this->reviewer);
        $receipt = $receipt->fresh('items');
        $returnLine = $receipt->items->firstWhere('product_id', $this->product('LABEL')->id);
        app(Purchasing\CreatePurchaseReturnAction::class)(PurchasingData\PurchaseReturnData::fromArray([
            'goods_receipt_id' => $receipt->id, 'returned_at' => $date, 'reason' => 'SIM2-Label tidak sesuai pesanan',
            'items' => [['goods_receipt_item_id' => $returnLine->id, 'quantity' => 1]],
        ]), $this->actor);
        $raw = app(Production\CreateRawMaterialOrderAction::class)(\App\Domain\Manufacturing\DTOs\RawMaterialOrderData::fromArray([
            'order_date' => $date, 'production_order_number' => $order->number,
            'items' => $order->materials->map(fn ($item) => ['production_order_material_id' => $item->id, 'quantity' => 25])->all(),
        ]), $this->actor);
        $issue = app(Inventory\IssueRawMaterialOrderItemAction::class)->issueMany(
            $raw->items->map(fn ($item) => ['raw_material_order_item_id' => $item->id, 'quantity' => 25])->all(), $date, $this->actor);
        app(Production\ApproveWarehouseMaterialReceiptAction::class)($issue, $this->company, $this->reviewer);
        app(\App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued::class)($order->fresh());
        $order->update(['status' => 'in_progress']);
        app(\App\Domain\Audit\Services\AuditLogService::class)->log('IN_PROGRESS', 'PRODUCTION_ORDER',
            'SIM2-Memulai '.$order->number, $order, ['status' => 'released'], ['status' => 'in_progress'], $this->actor);
        $base = ['production_order_id' => $order->id, 'material_product_id' => $this->product('FABRIC')->id, 'material_waste_quantity' => 0];
        app(Production\SaveCuttingAction::class)->create($base + ['input_quantity' => 25, 'send_to_sewing' => 24, 'rejected_quantity' => 1, 'cutting_date' => $date], $this->actor);
        app(Production\SaveSewingAction::class)->create($base + ['input_quantity' => 22, 'send_to_packing' => 21, 'rejected_quantity' => 1, 'sewing_date' => $date], $this->actor);
        app(Production\SavePackingAction::class)->create($base + ['input_quantity' => 20, 'send_to_finished_goods' => 19, 'rejected_quantity' => 1, 'packing_date' => $date], $this->actor);
        app(Production\SaveFinishedGoodsAction::class)->create(['production_order_id' => $order->id,
            'receive_from_packing' => 18, 'finished_good_quantity' => 18, 'finished_goods_date' => $date], $this->actor);
        $note = app(Production\SaveGoodsSentNoteAction::class)->create(['sent_date' => $date, 'contract_number' => 'SIM2-SALES-'.$index,
            'warehouse' => 'SIM-Gudang Barang Jadi', 'items' => [['production_order_id' => $order->id,
                'product_id' => $order->output_product_id, 'customer_id' => $this->customer->id, 'quantity' => 10, 'cartons' => 1]]], $this->actor);
        app(Production\SaveGoodsSentNoteAction::class)->approve($note, $this->reviewer);
        $receipt = $receipt->fresh('items'); $note = $note->fresh('items');
        $outgoingType = ['30', '261', '27Out', '41', '25'][$index - 1];
        $this->bc($incomingType, $receipt, $date);
        $this->bc($outgoingType, $note, $date);
        return compact('order', 'mr', 'pr', 'po', 'receipt', 'raw', 'issue', 'note', 'date');
    }

    private function bc(string $type, $source, string $date): void
    {
        $incoming = $source instanceof GoodsReceipt;
        $items = $source->items->map(function ($item) use ($incoming, $date, $type) {
            $line = ['product_id' => $item->product_id, 'quantity' => $item->quantity,
                'unit_price' => $incoming ? (float) $item->unit_price : 100000, 'hs_code' => '61091000'];
            if ($incoming) $line += ['goods_receipt_item_id' => $item->id, 'receipt_number' => $item->goodsReceipt->number,
                'receipt_date' => $date, 'country_code' => 'ID'];
            else $line += ['goods_sent_note_item_id' => $item->id, 'shipment_number' => $item->note->number, 'shipment_date' => $date];
            if ($type === '25') $line['item_per_unit'] = 1;
            if (in_array($type, ['40', '41'])) $line += ['tax_invoice_number' => 'SIM2-FAKTUR-'.$type, 'tax_invoice_date' => $date];
            return $line;
        })->all();
        $data = ['submission_number' => 'SIM2-AJU-'.$type, 'document_number' => 'SIM2-DOC-'.$type,
            'document_date' => $date, 'bl_awb_number' => 'SIM2-BL-'.$type, 'bl_awb_date' => $date,
            'invoice_number' => 'SIM2-INV-'.$type, 'invoice_date' => $date, 'currency' => 'IDR', 'price_type' => 'FOB', 'items' => $items];
        $data += $incoming ? ['supplier_id' => $this->supplier->id, 'importer_id' => Importer::where('tax_number', 'SIM-IMPORTER')->value('id')]
            : ['customer_id' => $this->customer->id, 'exporter_id' => Exporter::where('tax_number', 'SIM-EXPORTER')->value('id')];
        app('App\\Domain\\Customs\\Actions\\SaveBc'.$type.'Action')->save(null, $data, $this->company, $this->actor, true);
    }

    private function warehouseAndInventory(): void
    {
        foreach ($this->chains as $chain) {
            $return = app(Inventory\CreateProductionReturnAction::class)(['product_id' => $this->product('FABRIC')->id,
                'raw_material_order_document_id' => $chain['raw']->id, 'quantity' => 1, 'return_date' => $chain['date'],
                'po_supplier_number' => $chain['po']->number, 'description' => 'SIM2-Bahan sisa dari '.$chain['order']->number], $this->actor);
            app(Inventory\ApproveProductionReturnAction::class)($return, $this->company, $this->reviewer);
            app(Production\SaveSupportMaterialOrderItemAction::class)->create(['product_id' => $this->product('LABEL')->id,
                'quantity' => 5, 'order_date' => $chain['date'], 'po_other' => $chain['po']->number, 'style' => 'SIM-POLO'], $this->actor);
        }
        // Preserve existing pending return examples and add an approved FG warehouse return.
        $return = app(Inventory\CreateProductionReturnAction::class)(['product_id' => $this->product('POLO')->id,
            'quantity' => 1, 'return_date' => $this->date(), 'description' => 'SIM2-Barang jadi dikembalikan dari produksi untuk pemeriksaan'], $this->actor);
        app(Inventory\ApproveProductionReturnAction::class)($return, $this->company, $this->reviewer);
        $opname = app(Inventory\CreateStockOpnameAction::class)(new \App\Domain\Inventory\DTOs\StockOpnameData(
            [$this->product('FABRIC')->id, $this->product('LABEL')->id, $this->product('POLO')->id], 'SIM2-Pencacahan sesuai saldo fisik'), $this->actor);
        app(Inventory\SaveStockOpnameCountsAction::class)($opname, new \App\Domain\Inventory\DTOs\StockOpnameCountsData(
            $opname->items->map(fn ($item) => ['product_id' => $item->product_id, 'counted_quantity' => $item->system_quantity])->all()), $this->actor);
        app(Inventory\PostStockOpnameAction::class)($opname, $this->reviewer);
    }

    private function subcontract(): void
    {
        $partner = Subcontractor::where('company_id', $this->company)->where('name', 'SIM-PT Mitra Subkon')->firstOrFail();
        $contract = app(Subcontract\SaveSubcontractContractAction::class)->create(['subcontractor_id' => $partner->id,
            'starts_on' => $this->date(3), 'ends_on' => now()->addDays(30)->toDateString(), 'quota' => 200,
            'description' => 'SIM2-Kontrak jahit panel polo'], $this->company, $this->actor);
        foreach (['raw' => 'FABRIC', 'wip' => 'POLO/CUT', 'receipt' => 'POLO/CUT'] as $kind => $key) {
            foreach ([true, false] as $confirm) {
                $doc = app(Subcontract\SaveSubcontractMaterialIssueAction::class)->save(null, ['issue_kind' => $kind,
                    'issue_date' => $this->date(), 'contract_id' => $contract->id, 'contract_number' => $contract->contract_number,
                    'subcontractor_id' => $partner->id, 'invoice_number' => 'SIM2-SUB-INV', 'invoice_date' => $this->date(),
                    'items' => [['product_id' => $this->product($key)->id, 'quantity' => $confirm ? 5 : 2,
                        'style' => 'SIM-POLO', 'description' => 'SIM2-'.($confirm ? 'Dikonfirmasi' : 'Draft')]]], $this->company, $this->actor);
                if ($confirm) app($kind === 'receipt' ? Subcontract\ConfirmSubcontractReceiptAction::class : Subcontract\ConfirmSubcontractMaterialIssueAction::class)($doc, $this->company, $this->reviewer);
            }
        }
    }

    private function accounting(): void
    {
        $bank = CashBankAccount::where('company_id', $this->company)->where('code', 'SIM-BANK')->firstOrFail();
        foreach ($this->chains as $chain) {
            app(Accounting\PaySupplierAction::class)($chain['receipt'], ['cash_bank_account_id' => $bank->id,
                'payment_date' => $this->date(), 'amount' => 1000000, 'reference_number' => 'SIM2-PAY-'.$chain['order']->id,
                'notes' => 'SIM2-Pembayaran sebagian; sisa utang tetap tampil'], $this->actor);
        }
        $expense = ChartOfAccount::where('company_id', $this->company)->where('code', '6100')->firstOrFail();
        app(Accounting\RecordCashBankTransactionAction::class)(AccountingData\CashBankTransactionData::fromArray([
            'cash_bank_account_id' => $bank->id, 'counter_chart_account_id' => $expense->id,
            'transaction_date' => $this->date(), 'direction' => 'out', 'amount' => 500000,
            'reference_number' => 'SIM2-EXPENSE', 'description' => 'SIM2-Beban operasional latihan']), $this->actor);
        $entry = app(Accounting\CreateJournalEntryAction::class)(AccountingData\JournalEntryData::fromArray([
            'journal_date' => $this->date(), 'reference_number' => 'SIM2-ACCRUAL', 'description' => 'SIM2-Akrual biaya operasional',
            'lines' => [['chart_of_account_id' => $expense->id, 'debit' => 250000, 'credit' => 0],
                ['chart_of_account_id' => ChartOfAccount::where('company_id', $this->company)->where('code', '2110')->value('id'), 'debit' => 0, 'credit' => 250000]]]), $this->actor);
        app(Accounting\PostJournalEntryAction::class)($entry, $this->reviewer);
    }

    private function validateBalances(): void
    {
        foreach ($this->products as $product) {
            $stock = ProductStock::where('product_id', $product->id)->value('quantity') ?? 0;
            $lots = \App\Models\InventoryLot::where('product_id', $product->id)->sum('current_quantity');
            if ($stock < 0 || abs((float) $stock - (float) $lots) > 0.0001) {
                throw new RuntimeException('Saldo stok/lot simulasi tidak cocok: '.$product->code.'. Seluruh tambahan V2 dibatalkan.');
            }
        }
    }
}
