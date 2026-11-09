<?php

namespace App\Domain\Purchasing\Actions;

use App\Domain\Companies\Services\CompanyPdfBrandingService;
use App\Domain\Purchasing\Queries\PurchaseOrderTableQuery;
use App\Domain\Settings\Services\EmailSettingService;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderEmailLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendPurchaseOrderEmailAction
{
    public function __construct(
        private PurchaseOrderTableQuery $query,
        private EmailSettingService $emailSettings,
        private CompanyPdfBrandingService $branding,
    ) {}

    public function __invoke(PurchaseOrder $purchaseOrder, string $recipient, int $userId): void
    {
        $order = $this->query->details($purchaseOrder);

        if (!in_array($order->status, ['issued', 'partially_received', 'received'], true)) {
            throw ValidationException::withMessages([
                'purchase_order' => 'Purchase Order harus diterbitkan sebelum dikirim melalui email.',
            ]);
        }

        $setting = $this->emailSettings->configureMailer();
        $branding = $this->branding->data();
        $pdf = Pdf::loadView('super.purchase-orders.pdf', [
            ...$branding,
            'purchaseOrder' => $order,
        ])->setPaper('a4')->output();

        $filename = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $order->number), '-_.') . '.pdf';

        $subject = "Purchase Order {$order->number}";
        $sentMessage = Mail::mailer($setting->mail_mailer)
            ->send('super.purchase-orders.email', [
                'purchaseOrder' => $order,
                'company' => $branding['company'],
            ], function ($message) use ($recipient, $setting, $order, $subject, $filename, $pdf) {
                $message->to($recipient)
                    ->from($setting->from_email, $setting->from_name ?: $setting->from_email)
                    ->subject($subject)
                    ->attachData($pdf, $filename, ['mime' => 'application/pdf']);
            });

        if (!$sentMessage) {
            throw new \RuntimeException('Mail transport tidak mengirimkan pesan.');
        }

        PurchaseOrderEmailLog::create([
            'purchase_order_id' => $order->id,
            'sent_by' => $userId,
            'recipient' => $recipient,
            'subject' => $subject,
            'sent_at' => now(),
        ]);
    }
}
