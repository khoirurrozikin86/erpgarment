<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Audit\Services\AuditLogService;
use App\Domain\Accounting\Services\AutomaticJournalService;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IssueSalesInvoiceAction
{
    public function __construct(private AuditLogService $auditLog, private AutomaticJournalService $journals) {}

    public function __invoke(SalesInvoice $invoice, int $userId): SalesInvoice
    {
        return DB::transaction(function () use ($invoice, $userId) {
            $invoice = SalesInvoice::query()->lockForUpdate()->findOrFail($invoice->id);
            if ($invoice->status !== 'draft') throw ValidationException::withMessages(['status' => 'Hanya invoice draft yang dapat diterbitkan.']);
            if (!$invoice->items()->exists()) throw ValidationException::withMessages(['items' => 'Invoice tidak memiliki barang.']);
            $invoice->update(['status' => 'issued', 'issued_by' => $userId, 'issued_at' => now()]);
            $this->journals->recordSalesInvoice($invoice, $userId);
            $this->auditLog->log(action: 'ISSUE', module: 'SALES_INVOICE', description: "Menerbitkan Sales Invoice {$invoice->number}", model: $invoice, oldValues: ['status' => 'draft'], newValues: ['status' => 'issued']);
            return $invoice->load(['delivery', 'salesOrder', 'customer', 'creator', 'issuer', 'items.product.unit']);
        });
    }
}
