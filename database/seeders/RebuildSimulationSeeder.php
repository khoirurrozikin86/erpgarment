<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Schema};
use RuntimeException;

/** Destructive, explicit opt-in. Never called by DatabaseSeeder or deployment. */
class RebuildSimulationSeeder extends Seeder
{
    protected bool $seedSimulation = true;
    protected array $additionalResetTables = [];
    protected array $preservedTables = [];

    protected function afterReset(): void {}

    public function run(): void
    {
        $tables = explode(' ', 'stock_movement_lot_allocations inventory_lots stock_movements product_stocks customer_return_items customer_returns journal_entry_lines journal_entries production_order_stage_entries raw_material_order_items raw_material_order_documents equipment_issue_items equipment_issue_documents sales_invoice_payments purchase_request_items purchase_requests subcontract_material_issue_items subcontract_material_issue_documents production_order_materials production_orders production_order_headers non_production_material_usage_items non_production_material_usage_documents scrap_issue_documents equipment_receipt_items equipment_receipt_documents lost_item_lines lost_item_documents supplier_payments sales_invoice_email_logs sales_invoice_items sales_invoices delivery_items deliveries sales_order_items sales_orders stock_opname_items stock_opnames bill_of_material_items bill_of_materials material_request_items material_requests purchase_order_email_logs support_material_order_items support_material_order_documents subcontract_semi_finished_items subcontract_semi_finished_documents sales_quotation_email_logs sales_quotation_items sales_quotations damaged_item_lines damaged_item_documents material_issue_items material_issue_documents purchase_return_items purchase_returns goods_sent_note_items goods_sent_notes cash_bank_transactions production_return_items production_return_documents wip_issue_lines wip_issue_documents scrap_receipt_documents purchase_order_items purchase_orders goods_receipt_items goods_receipts customs_document_items customs_documents subcontract_contracts');
        $tables = array_values(array_unique(array_merge($tables, $this->additionalResetTables)));
        $tables = array_values(array_filter($tables, fn ($table) =>
            !in_array($table, $this->preservedTables, true) && Schema::hasTable($table)
        ));
        $directory = storage_path('app/private/simulation-backups');
        if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('Tidak bisa membuat direktori backup.');
        $path = $directory.'/transactions-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json.gz';
        $backup = gzopen($path, 'wb');
        if (!$backup) throw new RuntimeException('Backup tidak bisa dibuat; data tidak dihapus.');
        try {
            DB::transaction(function () use ($tables, &$backup) {
                // Capture data and remove it within the same transaction. Masters/users are excluded.
                foreach ($tables as $table) {
                    $payload = json_encode(['table' => $table, 'rows' => DB::table($table)->get()], JSON_THROW_ON_ERROR)."\n";
                    if (gzwrite($backup, $payload) !== strlen($payload)) throw new RuntimeException('Backup gagal; reset dibatalkan.');
                }
                $closed = gzclose($backup);
                $backup = null;
                if (!$closed) throw new RuntimeException('Backup gagal ditutup; reset dibatalkan.');
                Schema::disableForeignKeyConstraints();
                try {
                    foreach ($tables as $table) DB::table($table)->delete();
                } finally {
                    Schema::enableForeignKeyConstraints();
                }
                // Keep historical audit records; only seed idempotency markers are reset.
                DB::table('audit_logs')->where('module', 'SIMULATION')->where('description', 'like', 'SIMULATION-V%-COMPANY-%')->delete();
                if ($this->seedSimulation) $this->call(SimulationSeeder::class);
                $this->afterReset();
            });
        } finally {
            if ($backup !== null) gzclose($backup);
            chmod($path, 0600);
        }
        $this->command?->info(($this->additionalResetTables ? 'Reset transaksi dan item selesai. Akun dan master pendukung dipertahankan.' : ($this->seedSimulation ? 'Reset dan simulasi selesai.' : 'Seluruh transaksi dikosongkan tanpa simulasi baru. Akun dan master dipertahankan.')).' Backup transaksi: '.$path);
    }
}
