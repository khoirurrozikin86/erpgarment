<?php

namespace App\Domain\Suppliers\DTOs;

class SupplierData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $address,
        public ?string $contactPerson,
        public ?string $phone,
        public ?string $fax,
        public ?string $email,
        public ?string $country,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            address: $data['address'] ?? null,
            contactPerson: $data['contact_person'] ?? null,
            phone: $data['phone'] ?? null,
            fax: $data['fax'] ?? null,
            email: $data['email'] ?? null,
            country: $data['country'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'contact_person' => $this->contactPerson,
            'phone' => $this->phone,
            'fax' => $this->fax,
            'email' => $this->email,
            'country' => $this->country,
            'is_active' => $this->isActive,
        ];
    }
}
