<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Schema};

/** Rename only the legacy company prefix; keep sequence counters and foreign keys. */
class SdiDocumentNumberingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $changes = [];
            $columns = [
                'document_numberings' => ['prefix', 'format'],
                'subcontract_contracts' => ['contract_number'],
                'subcontract_material_issue_documents' => ['number', 'contract_number', 'invoice_number'],
                'subcontract_semi_finished_documents' => ['number', 'buyer_po_number'],
                'material_requests' => ['number'], 'purchase_requests' => ['number'],
                'purchase_orders' => ['number'], 'goods_receipts' => ['number', 'customs_document_number', 'invoice_number'],
                'purchase_returns' => ['number'], 'production_orders' => ['number', 'buyer_po_number'],
                'raw_material_order_documents' => ['number', 'production_order_number'],
                'raw_material_order_items' => ['number'], 'material_issue_documents' => ['number'],
                'support_material_order_documents' => ['number'], 'support_material_order_items' => ['po_other'],
                'production_return_documents' => ['number', 'po_supplier_number'],
                'goods_sent_notes' => ['number', 'contract_number'],
                'scrap_receipt_documents' => ['number', 'production_number'], 'scrap_issue_documents' => ['number'],
                'equipment_receipt_documents' => ['number', 'invoice_number'], 'equipment_receipt_items' => ['supplier_po_number'],
                'equipment_issue_documents' => ['number'], 'wip_issue_documents' => ['number'],
                'non_production_material_usage_documents' => ['number'], 'stock_opnames' => ['number'],
                'damaged_item_documents' => ['number'], 'lost_item_documents' => ['number'],
                'production_order_stage_entries' => ['cutting_number', 'sewing_number', 'packing_number', 'finished_goods_number'],
                'customs_documents' => ['submission_number', 'document_number', 'bl_awb_number', 'invoice_number'],
                'customs_document_items' => ['receipt_number', 'shipment_number', 'tax_invoice_number'],
                'supplier_payments' => ['number', 'reference_number'],
                'journal_entries' => ['number', 'reference_number'], 'cash_bank_transactions' => ['reference_number'],
            ];
            foreach ($columns as $table => $names) {
                if (!Schema::hasTable($table)) continue;
                foreach ($names as $column) {
                    if (!Schema::hasColumn($table, $column)) continue;
                    $query = DB::table($table);
                    $column === 'format'
                        ? $query->where($column, 'like', '%TIMW%')
                        : $query->where($column, 'like', 'TIMW%');
                    foreach ($query->lockForUpdate()->get(['id', $column]) as $row) {
                        $old = $row->{$column};
                        $new = $column === 'format' ? str_replace('TIMW', 'SDI', $old) : 'SDI'.substr($old, 4);
                        DB::table($table)->where('id', $row->id)->update([$column => $new]);
                        $changes[] = ['table' => $table, 'id' => $row->id, 'column' => $column, 'old' => $old, 'new' => $new];
                    }
                }
            }
            if ($changes) AuditLog::create(['action' => 'UPDATE', 'module' => 'DOCUMENT_NUMBERING',
                'description' => 'Mengganti awalan TIMW menjadi SDI sesuai permintaan pengguna; nomor urut tetap.',
                'old_values' => ['prefix' => 'TIMW'], 'new_values' => ['changes' => $changes]]);
            $this->command?->info('Penomoran SDI: '.count($changes).' nilai diperbarui; nomor urut tetap.');
        });
    }
}
