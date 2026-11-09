<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Sales\Queries\SalesInvoiceTableQuery;
use App\Domain\Settings\Services\EmailSettingService;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceEmailLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendSalesInvoiceEmailAction
{
    public function __construct(private SalesInvoiceTableQuery $query, private EmailSettingService $emailSettings, private CompanyPdfBrandingService $branding) {}

    public function __invoke(SalesInvoice $invoice, string $recipient, int $userId): void
    {
        $invoice = $this->query->details($invoice);
        if ($invoice->status !== 'issued') throw ValidationException::withMessages(['invoice' => 'Sales Invoice harus diterbitkan sebelum dikirim melalui email.']);
        $setting = $this->emailSettings->configureMailer();
        $branding = $this->branding->data();
        $pdf = Pdf::loadView('super.sales-invoices.pdf', [...$branding, 'invoice' => $invoice])->setPaper('a4')->output();
        $filename = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->number), '-_.') . '.pdf';
        $subject = "Sales Invoice {$invoice->number}";
        $sent = Mail::mailer($setting->mail_mailer)->send('super.sales-invoices.email', [
            'invoice' => $invoice, 'company' => $branding['company'],
        ], function ($message) use ($recipient, $setting, $filename, $subject, $pdf) {
            $message->to($recipient)->from($setting->from_email, $setting->from_name ?: $setting->from_email)
                ->subject($subject)->attachData($pdf, $filename, ['mime' => 'application/pdf']);
        });
        if (!$sent) throw new \RuntimeException('Mail transport tidak mengirimkan pesan.');
        SalesInvoiceEmailLog::create(['sales_invoice_id' => $invoice->id, 'sent_by' => $userId, 'recipient' => $recipient, 'subject' => $subject, 'sent_at' => now()]);
    }
}
