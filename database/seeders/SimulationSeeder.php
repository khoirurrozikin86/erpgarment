<?php

namespace Database\Seeders;

use App\Domain\Companies\Services\CompanyContext;
use App\Domain\Manufacturing\Actions as Manufacturing;
use App\Domain\Purchasing\Actions as Purchasing;
use App\Domain\Purchasing\DTOs as PurchaseData;
use App\Domain\Inventory\Actions as Inventory;
use App\Domain\Subcontract\Actions as Subcontract;
use App\Models\{AuditLog, BillOfMaterial, Category, Company, Customer, Exporter, Importer, Product, Style, Subcontractor, Supplier, Unit, User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{Auth, DB, Hash};
use RuntimeException;

/** Explicit opt-in demo. All stock changes go through normal application actions. */
class SimulationSeeder extends Seeder
{
    private int $operator;
    private int $reviewer;
    private int $companyId;
    private string $date;
    private array $products = [];
    private Supplier $supplier;
    private Customer $customer;

    public function run(): void
    {
        $context = app(CompanyContext::class);
        $previousCompany = $context->has() ? $context->get() : null;
        $previousUser = Auth::user();
        $company = Company::where('is_active', true)->where('code', 'SD001')->first()
            ?? Company::where('is_active', true)->orderBy('id')->first();
        if (!$company) throw new RuntimeException('Jalankan DatabaseSeeder terlebih dahulu: perusahaan aktif belum tersedia.');
        $this->companyId = $company->id;
        $this->date = now()->toDateString();
        $marker = 'SIMULATION-V1-COMPANY-'.$company->id;

        try {
            $context->set($company);
            DB::transaction(function () use ($company, $marker) {
                // Serializes concurrent runs and commits the marker only with the entire scenario.
                Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
                if (AuditLog::where('module', 'SIMULATION')->where('description', $marker)->exists()) {
                    $this->command?->info('Simulasi sudah tersedia; tidak membuat transaksi/stok ganda.');
                    return;
                }
                $this->call(DocumentNumberingSeeder::class);
                app(\App\Domain\Accounting\Services\ChartOfAccountDefaultsService::class)->seedForCompany($company);
                $this->masters();
                Auth::setUser(User::findOrFail($this->operator));
                $this->command?->info('Menyiapkan pembelian dan produksi simulasi...');
                [$order, $receipt, $note] = $this->production();
                $this->inventory($order, $receipt);
                $this->subcontract($order);
                $this->customs($receipt, $note);
                $this->accounting();
                AuditLog::create(['user_id' => $this->operator, 'action' => 'SEED', 'module' => 'SIMULATION',
                    'description' => $marker, 'new_values' => ['company_id' => $company->id, 'date' => $this->date,
                        'products' => collect($this->products)->map->id->all()]]);
                $this->command?->info('Simulasi lengkap dibuat untuk '.$company->name.' ('.$this->date.').');
            });
            $this->call(SimulationModulesSeeder::class);
            $this->call(SimulationCompletionSeeder::class);
        } finally {
            $previousCompany ? $context->set($previousCompany) : $context->clear();
            $previousUser ? Auth::setUser($previousUser) : Auth::forgetUser();
        }
    }

    private function masters(): void
    {
        // Existing account credentials and roles are never overwritten.
        foreach (['operator', 'reviewer'] as $role) {
            $user = User::firstOrCreate(['email' => 'sim.'.$role.'@localhost.test'],
                ['name' => 'SIM-'.ucfirst($role), 'password' => Hash::make('passwrod')]);
            $this->{$role} = $user->id;
        }
        $this->supplier = Supplier::firstOrCreate(['code' => 'SIM-SUPPLIER'],
            ['name' => 'SIM-Supplier Garment', 'is_active' => true, 'email' => 'supplier@example.test', 'address' => 'Data simulasi']);
        $this->customer = Customer::firstOrCreate(['code' => 'SIM-CUSTOMER'],
            ['name' => 'SIM-Customer Garment', 'is_active' => true, 'email' => 'buyer@example.test', 'country' => 'Indonesia']);
        Style::firstOrCreate(['code' => 'SIM-POLO'], ['name' => 'SIM-Polo Shirt']);
        foreach ([['FAB', 'Fabric'], ['ACC', 'Accessories'], ['FG', 'Finished Goods'], ['WIP', 'Work in Process'],
            ['WASTE', 'Scrap / Reject'], ['MACHINE', 'Machine'], ['EQUIPMENT', 'Equipment']] as [$code, $name]) {
            Category::firstOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
        foreach (['PCS', 'MTR', 'KG', 'UNIT'] as $code) {
            Unit::firstOrCreate(['code' => $code], ['name' => $code, 'symbol' => $code, 'is_active' => true]);
        }
        foreach ([
            ['FABRIC', 'Kain polo', 'FAB', 'MTR', 'fabric', 40000],
            ['LABEL', 'Label polo', 'ACC', 'PCS', 'accessory', 1000],
            ['POLO', 'Polo shirt hasil produksi', 'FG', 'PCS', 'finished_good', 65000],
            ['POLO/CUT', 'Panel polo WIP', 'WIP', 'PCS', 'work_in_process', 30000],
            ['SCRAP', 'Scrap kain', 'WASTE', 'KG', 'other', 0],
            ['REJECT', 'Polo reject', 'WASTE', 'PCS', 'other', 0],
            ['MACHINE', 'Mesin jahit', 'MACHINE', 'UNIT', 'other', 5000000],
            ['EQUIPMENT', 'Peralatan potong', 'EQUIPMENT', 'UNIT', 'other', 500000],
        ] as [$key, $name, $category, $unit, $type, $price]) {
            // Refuse to consume stock from an earlier, incomplete/manual SIM dataset.
            if (Product::where('code', 'SIM-'.$key)->exists()) {
                throw new RuntimeException('Kode SIM-'.$key.' sudah dipakai tanpa marker simulasi. Gunakan database simulasi bersih; data tidak diubah.');
            }
            $this->products[$key] = Product::create(['code' => 'SIM-'.$key, 'name' => 'SIM-'.$name,
                'category_id' => Category::where('code', $category)->value('id'), 'unit_id' => Unit::where('code', $unit)->value('id'),
                'product_type' => 'stock', 'garment_item_type' => $type, 'style_code' => 'SIM-POLO',
                'purchase_price' => $price, 'track_stock' => true, 'is_active' => true]);
        }
    }

    private function production(): array
    {
        $bom = BillOfMaterial::create(['company_id' => $this->companyId, 'product_id' => $this->id('POLO'),
            'version' => 'SIM-1', 'name' => 'SIM-BOM Polo', 'status' => 'active', 'output_quantity' => 1, 'notes' => 'SIM-Data latihan']);
        $bom->items()->createMany([
            ['product_id' => $this->id('FABRIC'), 'quantity' => 1, 'wastage_percent' => 0, 'operation' => 'Cutting'],
            ['product_id' => $this->id('LABEL'), 'quantity' => 1, 'wastage_percent' => 0, 'operation' => 'Sewing'],
        ]);
        $order = app(Manufacturing\CreateProductionOrderAction::class)([
            'number' => 'SIM-MO-001', 'buyer_po_number' => 'SIM-BUYER-001', 'currency' => 'IDR',
            'po_date' => $this->date, 'due_date' => now()->addDays(14)->toDateString(), 'customer_id' => $this->customer->id,
            'output_product_id' => $this->id('POLO'), 'planned_quantity' => 100, 'price_per_unit' => 100000,
        ], $this->operator);
        $mr = app(Purchasing\CreateMaterialRequestAction::class)(PurchaseData\MaterialRequestData::fromArray([
            'department' => 'SIM-PPIC', 'reason' => 'SIM-Pembelian stok dan produksi', 'production_order_id' => $order->id,
            'items' => collect(['FABRIC', 'LABEL', 'POLO/CUT'])->map(fn ($key) => ['product_id' => $this->id($key), 'quantity' => 500])->all(),
        ]), $this->operator);
        app(Purchasing\ReviewMaterialRequestAction::class)($mr, new PurchaseData\MaterialRequestReviewData($this->reviewer, 'approved', 'SIM-Disetujui'));
        $pr = app(Purchasing\CreatePurchaseRequestAction::class)(PurchaseData\PurchaseRequestData::fromArray([
            'material_request_id' => $mr->id, 'supplier_id' => $this->supplier->id, 'notes' => 'SIM-Pengadaan',
            'items' => $mr->items->map(fn ($item) => ['material_request_item_id' => $item->id, 'quantity' => 500,
                'unit_price' => (float) $item->product->purchase_price])->all(),
        ]), $this->operator);
        app(Purchasing\ReviewPurchaseRequestAction::class)($pr, new PurchaseData\PurchaseRequestReviewData($this->reviewer, 'approved', 'SIM-Disetujui'));
        $po = app(Purchasing\CreatePurchaseOrderAction::class)(PurchaseData\PurchaseOrderData::fromArray([
            'purchase_request_id' => $pr->id, 'notes' => 'SIM-PO', 'payment_terms' => 'SIM-30 hari',
        ]), $this->operator);
        app(Purchasing\IssuePurchaseOrderAction::class)($po);
        $receipt = app(Purchasing\CreateGoodsReceiptAction::class)(PurchaseData\GoodsReceiptData::fromArray([
            'purchase_order_id' => $po->id, 'received_at' => $this->date, 'notes' => 'SIM-GRN disetujui', 'currency' => 'IDR',
            'invoice_number' => 'SIM-INV-001', 'invoice_date' => $this->date,
            'items' => $po->items->map(fn ($item) => ['purchase_order_item_id' => $item->id, 'quantity' => 400,
                'lot_number' => 'SIM-LOT-'.$item->product_id])->all(),
        ]), $this->operator);
        app(Purchasing\ApproveGoodsReceiptAction::class)($receipt, $this->reviewer);
        // Remaining 100 per item: pending warehouse confirmation; it must not increase stock.
        app(Purchasing\CreateGoodsReceiptAction::class)(PurchaseData\GoodsReceiptData::fromArray([
            'purchase_order_id' => $po->id, 'received_at' => $this->date, 'notes' => 'SIM-Menunggu konfirmasi gudang',
            'items' => $po->items->map(fn ($item) => ['purchase_order_item_id' => $item->id, 'quantity' => 100,
                'lot_number' => 'SIM-PENDING-'.$item->product_id])->all(),
        ]), $this->operator);
        $raw = app(Manufacturing\CreateRawMaterialOrderAction::class)(
            \App\Domain\Manufacturing\DTOs\RawMaterialOrderData::fromArray([
                'order_date' => $this->date, 'production_order_number' => $order->number,
                'items' => $order->materials->map(fn ($item) => ['production_order_material_id' => $item->id, 'quantity' => 100])->all(),
            ]), $this->operator);
        $issue = app(Inventory\IssueRawMaterialOrderItemAction::class)->issueMany(
            $raw->items->map(fn ($item) => ['raw_material_order_item_id' => $item->id, 'quantity' => 100])->all(), $this->date, $this->operator);
        app(Manufacturing\ApproveWarehouseMaterialReceiptAction::class)($issue, $this->companyId, $this->reviewer);
        app(\App\Domain\Manufacturing\Services\EnsureProductionMaterialsIssued::class)($order->fresh());
        $order->update(['status' => 'in_progress']);
        app(\App\Domain\Audit\Services\AuditLogService::class)->log('IN_PROGRESS', 'PRODUCTION_ORDER',
            'SIM-Memulai produksi setelah bahan dikeluarkan', $order, ['status' => 'released'], ['status' => 'in_progress'], $this->operator);
        $base = ['production_order_id' => $order->id, 'material_product_id' => $this->id('FABRIC'), 'material_waste_quantity' => 0];
        app(Manufacturing\SaveCuttingAction::class)->create($base + ['input_quantity' => 100, 'send_to_sewing' => 98, 'rejected_quantity' => 2, 'cutting_date' => $this->date], $this->operator);
        app(Manufacturing\SaveSewingAction::class)->create($base + ['input_quantity' => 90, 'send_to_packing' => 89, 'rejected_quantity' => 1, 'sewing_date' => $this->date], $this->operator);
        app(Manufacturing\SavePackingAction::class)->create($base + ['input_quantity' => 80, 'send_to_finished_goods' => 79, 'rejected_quantity' => 1, 'packing_date' => $this->date], $this->operator);
        app(Manufacturing\SaveFinishedGoodsAction::class)->create(['production_order_id' => $order->id,
            'receive_from_packing' => 79, 'finished_good_quantity' => 79, 'finished_goods_date' => $this->date], $this->operator);
        $note = app(Manufacturing\SaveGoodsSentNoteAction::class)->create(['sent_date' => $this->date,
            'items' => [['production_order_id' => $order->id, 'product_id' => $this->id('POLO'),
                'customer_id' => $this->customer->id, 'quantity' => 30, 'cartons' => 3]]], $this->operator);
        app(Manufacturing\SaveGoodsSentNoteAction::class)->approve($note, $this->reviewer);
        app(Manufacturing\SaveSupportMaterialOrderItemAction::class)->create(['product_id' => $this->id('LABEL'),
            'quantity' => 10, 'order_date' => $this->date, 'style' => 'SIM-POLO'], $this->operator);
        app(Purchasing\CreateMaterialRequestAction::class)(PurchaseData\MaterialRequestData::fromArray([
            'department' => 'SIM-Gudang', 'reason' => 'SIM-Permintaan menunggu approval',
            'items' => [['product_id' => $this->id('LABEL'), 'quantity' => 20]],
        ]), $this->operator);
        return [$order, $receipt->fresh('items'), $note->fresh('items')];
    }

    private function inventory($order, $receipt): void
    {
        app(Purchasing\CreatePurchaseReturnAction::class)(PurchaseData\PurchaseReturnData::fromArray([
            'goods_receipt_id' => $receipt->id, 'returned_at' => $this->date, 'reason' => 'SIM-Retur kualitas',
            'items' => [['goods_receipt_item_id' => $receipt->items->first()->id, 'quantity' => 2]],
        ]), $this->operator);
        // Retur stays pending: the seeder does not assume available production-return quantity.
        foreach (['FABRIC', 'POLO'] as $key) {
            app(Inventory\CreateProductionReturnAction::class)(['product_id' => $this->id($key),
                'quantity' => 1, 'return_date' => $this->date, 'description' => 'SIM-Retur menunggu konfirmasi gudang'], $this->operator);
        }
        foreach (['SCRAP', 'REJECT'] as $key) {
            app(Inventory\SaveScrapReceiptAction::class)->create(['scrap_product_id' => $this->id($key),
                'source_product_id' => $this->id('FABRIC'), 'receipt_date' => $this->date, 'quantity' => 10,
                'production_number' => $order->number, 'description' => 'SIM-Sisa produksi'], $this->operator);
        }
        app(Inventory\SaveScrapIssueAction::class)->create(['scrap_product_id' => $this->id('SCRAP'),
            'customer_id' => $this->customer->id, 'issue_date' => $this->date, 'quantity' => 3, 'description' => 'SIM-Pengeluaran scrap'], $this->operator);
        foreach (['MACHINE', 'EQUIPMENT'] as $key) {
            app(Inventory\SaveEquipmentReceiptAction::class)->create(['receipt_date' => $this->date,
                'ownership_type' => 'own', 'supplier_id' => $this->supplier->id, 'currency_code' => 'IDR', 'description' => 'SIM-Penerimaan peralatan',
                'items' => [['product_id' => $this->id($key), 'quantity' => 5, 'unit_price' => $this->products[$key]->purchase_price]]], $this->operator);
            app(Inventory\SaveEquipmentIssueAction::class)->create(['issue_date' => $this->date,
                'product_id' => $this->id($key), 'quantity' => 1, 'description' => 'SIM-Pengeluaran peralatan'], $this->operator);
        }
        app(Inventory\SaveNonProductionMaterialUsageAction::class)->create(['product_id' => $this->id('LABEL'),
            'quantity' => 1, 'unit_price' => 1000, 'transaction_date' => $this->date], $this->operator);
        app(Inventory\SaveWipIssueAction::class)->create(['product_id' => $this->id('POLO/CUT'),
            'quantity' => 5, 'transaction_date' => $this->date, 'description' => 'SIM-WIP keluar'], $this->operator);
        foreach ([Inventory\SaveDamagedItemAction::class, Inventory\SaveLostItemAction::class] as $action) {
            app($action)->create(['product_id' => $this->id('LABEL'), 'quantity' => 1,
                'document_date' => $this->date, 'description' => 'SIM-Berita acara latihan'], $this->operator);
        }
        app(Inventory\CreateStockOpnameAction::class)(new \App\Domain\Inventory\DTOs\StockOpnameData(
            collect($this->products)->map->id->values()->all(), 'SIM-Pencacahan draft, belum mengubah stok'), $this->operator);
    }

    private function subcontract($order): void
    {
        $partner = Subcontractor::firstOrCreate(['company_id' => $this->companyId, 'name' => 'SIM-PT Mitra Subkon'], ['is_active' => true]);
        $contract = app(Subcontract\SaveSubcontractContractAction::class)->create([
            'subcontractor_id' => $partner->id, 'description' => 'SIM-Kontrak bordir',
            'starts_on' => $this->date, 'ends_on' => now()->addMonth()->toDateString(), 'quota' => 1000,
        ], $this->companyId, $this->operator);
        foreach (['raw' => 'FABRIC', 'wip' => 'POLO/CUT', 'receipt' => 'POLO/CUT'] as $kind => $key) {
            $doc = app(Subcontract\SaveSubcontractMaterialIssueAction::class)->save(null, [
                'issue_kind' => $kind, 'issue_date' => $this->date, 'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number, 'subcontractor_id' => $partner->id,
                'invoice_number' => 'SIM-SUB-INV', 'invoice_date' => $this->date,
                'items' => [['product_id' => $this->id($key), 'quantity' => 10, 'style' => 'SIM-POLO', 'description' => 'SIM-Latihan subkon']],
            ], $this->companyId, $this->operator);
            $action = $kind === 'receipt' ? Subcontract\ConfirmSubcontractReceiptAction::class : Subcontract\ConfirmSubcontractMaterialIssueAction::class;
            app($action)($doc, $this->companyId, $this->reviewer);
        }
        app(Subcontract\SaveSubcontractSemiFinishedAction::class)->create(['production_date' => $this->date,
            'production_order_id' => $order->id, 'product_id' => $this->id('POLO/CUT'),
            'quantity' => 10, 'description' => 'SIM-Hasil setengah jadi'], $this->companyId, $this->operator);
    }

    private function customs($receipt, $note): void
    {
        $importer = Importer::firstOrCreate(['tax_number' => 'SIM-IMPORTER'], ['name' => 'SIM-Importir', 'address' => 'Data simulasi', 'is_active' => true]);
        $exporter = Exporter::firstOrCreate(['tax_number' => 'SIM-EXPORTER'], ['name' => 'SIM-Eksportir', 'address' => 'Data simulasi', 'is_active' => true]);
        foreach (['23', '262', '27', '40', '20', '30', '261', '27Out', '41', '25'] as $type) {
            $incoming = in_array($type, ['23', '262', '27', '40', '20'], true);
            $line = ['product_id' => $this->id($incoming ? 'FABRIC' : 'POLO'), 'quantity' => 1,
                'unit_price' => $incoming ? 40000 : 100000, 'hs_code' => 'SIMULATION'];
            if ($incoming) {
                $source = $receipt->items->firstWhere('product_id', $this->id('FABRIC'));
                $line += ['goods_receipt_item_id' => $source->id, 'receipt_number' => $receipt->number,
                    'receipt_date' => $this->date, 'country_code' => 'ID'];
            } else {
                $line += ['goods_sent_note_item_id' => $note->items->first()->id,
                    'shipment_number' => $note->number, 'shipment_date' => $this->date];
            }
            if ($type === '25') $line['item_per_unit'] = 1;
            $data = ['submission_number' => 'SIM-BC-'.$type, 'document_number' => 'SIM-DOC-'.$type,
                'document_date' => $this->date, 'bl_awb_number' => 'SIM-BL-'.$type, 'bl_awb_date' => $this->date,
                'invoice_number' => 'SIM-INV-'.$type, 'invoice_date' => $this->date,
                'price_type' => 'FOB', 'currency' => 'IDR', 'items' => [$line]];
            $data += $incoming ? ['supplier_id' => $this->supplier->id, 'importer_id' => $importer->id]
                : ['customer_id' => $this->customer->id, 'exporter_id' => $exporter->id];
            app('App\\Domain\\Customs\\Actions\\SaveBc'.$type.'Action')->save(null, $data, $this->companyId, $this->operator, true);
        }
    }

    private function accounting(): void
    {
        $accounts = \App\Models\ChartOfAccount::where('company_id', $this->companyId)->where('is_active', true)->where('is_group', false)->get()->keyBy('code');
        $bank = \App\Models\CashBankAccount::firstOrCreate(['company_id' => $this->companyId, 'code' => 'SIM-BANK'],
            ['name' => 'SIM-Bank latihan', 'type' => 'bank', 'chart_of_account_id' => $accounts['1120']->id, 'opening_balance' => 0, 'is_active' => true]);
        $equity = $accounts->first(fn ($account) => $account->account_type === 'equity');
        if (!$equity) throw new RuntimeException('COA modal belum tersedia untuk simulasi.');
        app(\App\Domain\Accounting\Actions\RecordCashBankTransactionAction::class)(
            \App\Domain\Accounting\DTOs\CashBankTransactionData::fromArray([
                'cash_bank_account_id' => $bank->id, 'counter_chart_account_id' => $equity->id,
                'transaction_date' => $this->date, 'direction' => 'in', 'amount' => 50000000,
                'description' => 'SIM-Modal latihan', 'reference_number' => 'SIM-CAPITAL',
            ]), $this->operator);
        app(\App\Domain\Accounting\Actions\CreateJournalEntryAction::class)(
            \App\Domain\Accounting\DTOs\JournalEntryData::fromArray(['journal_date' => $this->date,
                'description' => 'SIM-Jurnal draft untuk latihan posting', 'reference_number' => 'SIM-JOURNAL',
                'lines' => [['chart_of_account_id' => $accounts['1120']->id, 'debit' => 100000, 'credit' => 0],
                    ['chart_of_account_id' => $equity->id, 'debit' => 0, 'credit' => 100000]]]), $this->operator);
    }

    private function id(string $key): int
    {
        return $this->products[$key]->id;
    }
}
