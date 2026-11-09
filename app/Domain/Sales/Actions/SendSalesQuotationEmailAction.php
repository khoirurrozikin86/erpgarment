<?php

namespace App\Domain\Sales\Actions;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Sales\Queries\SalesQuotationTableQuery;
use App\Domain\Settings\Services\EmailSettingService;
use App\Models\SalesQuotation;
use App\Models\SalesQuotationEmailLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendSalesQuotationEmailAction
{
    public function __construct(
        private SalesQuotationTableQuery $query,
        private EmailSettingService $emailSettings,
        private CompanyPdfBrandingService $branding,
    ) {}

    public function __invoke(SalesQuotation $quotation, string $recipient, int $userId): void
    {
        $quotation = $this->query->details($quotation);
        if ($quotation->status !== 'sent') {
            throw ValidationException::withMessages(['quotation' => 'Quotation harus diterbitkan sebelum dikirim melalui email.']);
        }

        $setting = $this->emailSettings->configureMailer();
        $branding = $this->branding->data();
        $pdf = Pdf::loadView('super.sales-quotations.pdf', [...$branding, 'quotation' => $quotation])
            ->setPaper('a4')->output();
        $filename = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $quotation->number), '-_.') . '.pdf';
        $subject = "Sales Quotation {$quotation->number}";

        $sent = Mail::mailer($setting->mail_mailer)->send('super.sales-quotations.email', [
            'quotation' => $quotation,
            'company' => $branding['company'],
        ], function ($message) use ($recipient, $setting, $subject, $filename, $pdf) {
            $message->to($recipient)
                ->from($setting->from_email, $setting->from_name ?: $setting->from_email)
                ->subject($subject)
                ->attachData($pdf, $filename, ['mime' => 'application/pdf']);
        });

        if (!$sent) throw new \RuntimeException('Mail transport tidak mengirimkan pesan.');

        SalesQuotationEmailLog::create([
            'sales_quotation_id' => $quotation->id,
            'sent_by' => $userId,
            'recipient' => $recipient,
            'subject' => $subject,
            'sent_at' => now(),
        ]);
    }
}
