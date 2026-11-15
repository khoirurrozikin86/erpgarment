<?php

namespace App\Exports;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\EquipmentReceiptDocument;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping};

class EquipmentReceiptsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private int $companyId, private ?string $from, private ?string $to) {}
    public function collection()
    {
        return EquipmentReceiptDocument::query()->where('company_id', $this->companyId)->when($this->from, fn ($q) => $q->whereDate('receipt_date', '>=', $this->from))->when($this->to, fn ($q) => $q->whereDate('receipt_date', '<=', $this->to))->with(['supplier', 'items'])->orderBy('receipt_date')->get()->flatMap(fn ($doc) => $doc->items->map(fn ($item) => [$doc, $item]));
    }
    public function headings(): array { return [__('ui.common.no'), __('ui.machine_receipt.number'), __('ui.machine_receipt.date'), __('ui.machine_receipt.ownership'), __('ui.machine_receipt.invoice_number'), __('ui.machine_receipt.invoice_date'), __('ui.machine_receipt.supplier'), __('ui.machine_receipt.currency'), __('ui.common.item_code'), __('ui.common.item_name'), __('ui.common.unit'), __('ui.common.quantity'), __('ui.machine_receipt.unit_price'), __('ui.machine_receipt.total_price'), __('ui.machine_receipt.supplier_po'), __('ui.common.description')]; }
    public function map($row): array { [$doc, $item] = $row; return [$item->id, $doc->number, $doc->receipt_date?->format('Y-m-d'), __('ui.machine_receipt.ownership_'.$doc->ownership_type), $doc->invoice_number, $doc->invoice_date?->format('Y-m-d'), $doc->supplier?->name, $doc->currency_code, $item->item_code, $item->item_name, $item->unit, $item->quantity, $item->unit_price, $item->total_price, $item->supplier_po_number, $item->description]; }
}
