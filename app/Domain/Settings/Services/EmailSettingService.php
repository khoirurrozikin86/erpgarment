<?php

namespace App\Domain\Settings\Services;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailSettingService
{
    public function __construct(
        protected CompanyContext $companyContext
    ) {}

    public function get(): EmailSetting
    {
        $company = $this->companyContext->get();

        return $company->emailSetting()->firstOrCreate(
            [
                'company_id' => $company->id,
            ],
            [
                'mail_mailer' => 'smtp',
                'mail_port' => 587,
                'mail_encryption' => 'tls',
                'is_active' => false,
            ]
        );
    }

    public function update(array $data): EmailSetting
    {
        $setting = $this->get();

        /*
         * Password kosong berarti password lama
         * tidak diubah.
         */
        if (empty($data['mail_password'])) {
            unset($data['mail_password']);
        }

        $setting->update($data);

        return $setting->refresh();
    }

    public function configureMailer(): EmailSetting
    {
        $setting = $this->get();

        if (!$setting->is_active || !$setting->mail_host || !$setting->from_email) {
            throw ValidationException::withMessages([
                'email' => 'Aktifkan dan lengkapi Email Setting sebelum mengirim email.',
            ]);
        }

        config([
            'mail.default' => $setting->mail_mailer,
            "mail.mailers.{$setting->mail_mailer}.host" => $setting->mail_host,
            "mail.mailers.{$setting->mail_mailer}.port" => $setting->mail_port,
            "mail.mailers.{$setting->mail_mailer}.username" => $setting->mail_username,
            "mail.mailers.{$setting->mail_mailer}.password" => $setting->mail_password,
            "mail.mailers.{$setting->mail_mailer}.encryption" => $setting->mail_encryption,
            'mail.from.address' => $setting->from_email,
            'mail.from.name' => $setting->from_name,
        ]);

        Mail::purge($setting->mail_mailer);

        return $setting;
    }
}
