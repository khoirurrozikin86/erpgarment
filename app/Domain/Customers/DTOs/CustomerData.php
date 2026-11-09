<?php

namespace App\Domain\Customers\DTOs;

class CustomerData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $customerType = 'company',
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
        public float|string $creditLimit = 0,
        public ?string $notes = null,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            customerType: $data['customer_type'] ?? 'company',
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
            creditLimit: $data['credit_limit'] ?? 0,
            notes: $data['notes'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'customer_type' => $this->customerType,
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
            'credit_limit' => $this->creditLimit,
            'notes' => $this->notes,
            'is_active' => $this->isActive,
        ];
    }
}
