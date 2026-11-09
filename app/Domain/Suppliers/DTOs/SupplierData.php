<?php

namespace App\Domain\Suppliers\DTOs;

class SupplierData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $supplierType = 'company',
        public ?string $contactPerson = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $website = null,
        public ?string $taxNumber = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $province = null,
        public ?string $postalCode = null,
        public string $paymentTerm = 'COD',
        public ?string $bankName = null,
        public ?string $bankAccountNumber = null,
        public ?string $bankAccountName = null,
        public ?string $notes = null,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            supplierType: $data['supplier_type'] ?? 'company',
            contactPerson: $data['contact_person'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            website: $data['website'] ?? null,
            taxNumber: $data['tax_number'] ?? null,
            address: $data['address'] ?? null,
            city: $data['city'] ?? null,
            province: $data['province'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            paymentTerm: $data['payment_term'] ?? 'COD',
            bankName: $data['bank_name'] ?? null,
            bankAccountNumber: $data['bank_account_number'] ?? null,
            bankAccountName: $data['bank_account_name'] ?? null,
            notes: $data['notes'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'supplier_type' => $this->supplierType,
            'contact_person' => $this->contactPerson,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'tax_number' => $this->taxNumber,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,
            'payment_term' => $this->paymentTerm,
            'bank_name' => $this->bankName,
            'bank_account_number' => $this->bankAccountNumber,
            'bank_account_name' => $this->bankAccountName,
            'notes' => $this->notes,
            'is_active' => $this->isActive,
        ];
    }
}
