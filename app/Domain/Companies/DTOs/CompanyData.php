<?php

namespace App\Domain\Companies\DTOs;

class CompanyData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $logo = null,

        public ?string $email = null,
        public ?string $phone = null,
        public ?string $website = null,

        public ?string $taxNumber = null,

        public ?string $address = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $postalCode = null,

        public string $currency = 'IDR',
        public string $timezone = 'Asia/Jakarta',
        public string $dateFormat = 'd/m/Y',

        public ?string $invoiceHeader = null,
        public ?string $invoiceFooter = null,

        public ?string $receiptHeader = null,
        public ?string $receiptFooter = null,

        public bool $isActive = true,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            code: trim($data['code']),
            name: trim($data['name']),
            logo: self::nullableString($data['logo'] ?? null),

            email: self::nullableString($data['email'] ?? null),
            phone: self::nullableString($data['phone'] ?? null),
            website: self::nullableString($data['website'] ?? null),

            taxNumber: self::nullableString($data['tax_number'] ?? null),

            address: self::nullableString($data['address'] ?? null),
            city: self::nullableString($data['city'] ?? null),
            province: self::nullableString($data['province'] ?? null),
            postalCode: self::nullableString($data['postal_code'] ?? null),

            currency: trim($data['currency'] ?? 'IDR'),
            timezone: trim($data['timezone'] ?? 'Asia/Jakarta'),
            dateFormat: trim($data['date_format'] ?? 'd/m/Y'),

            invoiceHeader: self::nullableString($data['invoice_header'] ?? null),
            invoiceFooter: self::nullableString($data['invoice_footer'] ?? null),

            receiptHeader: self::nullableString($data['receipt_header'] ?? null),
            receiptFooter: self::nullableString($data['receipt_footer'] ?? null),

            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'logo' => $this->logo,

            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,

            'tax_number' => $this->taxNumber,

            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,

            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'date_format' => $this->dateFormat,

            'invoice_header' => $this->invoiceHeader,
            'invoice_footer' => $this->invoiceFooter,

            'receipt_header' => $this->receiptHeader,
            'receipt_footer' => $this->receiptFooter,

            'is_active' => $this->isActive,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}