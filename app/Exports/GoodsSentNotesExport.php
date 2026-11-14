<?php

namespace App\Exports;

use App\Models\GoodsSentNoteItem;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\{FromQuery, WithHeadings, WithMapping};

class GoodsSentNotesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private int $companyId, private ?string $from, private ?string $to) {}
    public function query(): Builder
    {
        return GoodsSentNoteItem::query()->whereHas('note', fn ($q) => $q->where('company_id', $this->companyId)->when($this->from, fn ($q) => $q->whereDate('sent_date', '>=', $this->from))->when($this->to, fn ($q) => $q->whereDate('sent_date', '<=', $this->to)))
            ->with(['note.creator', 'productionOrder.outputProduct.unit', 'customer'])->orderBy('goods_sent_note_id')->orderBy('id');
    }
    public function headings(): array { return ['No GSN', 'Tanggal GSN', 'No Kontrak', 'Tanggal Pesanan', 'Rute Kirim', 'Gudang', 'No Kendaraan', 'No Seal', 'No Container', 'No MO', 'Style', 'Item Code', 'Nama Item', 'Satuan', 'Qty', 'Cartons', 'PO Buyer', 'Customer', 'User Input']; }
    public function map($item): array
    {
        $note = $item->note; $order = $item->productionOrder; $product = $order?->outputProduct;
        return [$note?->number, $note?->sent_date?->format('Y-m-d'), $note?->contract_number, $note?->contract_date?->format('Y-m-d'), $note?->delivery_route, $note?->warehouse, $note?->vehicle_number, $note?->seal_number, $note?->container_number, $order?->number, $product?->style_code, $product?->code, $product?->name, $product?->unit?->symbol ?: $product?->unit?->name, $item->quantity, $item->cartons, $order?->buyer_po_number, $item->customer?->name, $note?->creator?->username ?: $note?->creator?->name];
    }
}
