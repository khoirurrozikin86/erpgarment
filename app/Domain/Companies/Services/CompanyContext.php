<?php

namespace App\Domain\Companies\Services;

use App\Models\Company;
use RuntimeException;

class CompanyContext
{
    protected ?Company $company = null;

    /**
     * Set company yang sedang digunakan.
     */
    public function set(Company $company): void
    {
        $this->company = $company;
    }

    /**
     * Ambil company yang sedang digunakan.
     */
    public function get(): Company
    {
        if (!$this->company) {
            throw new RuntimeException('Company context belum tersedia.');
        }

        return $this->company;
    }

    /**
     * Cek apakah company sudah tersedia.
     */
    public function has(): bool
    {
        return $this->company !== null;
    }

    /**
     * ID company aktif.
     */
    public function id(): int
    {
        return $this->get()->id;
    }

    /**
     * Bersihkan context.
     */
    public function clear(): void
    {
        $this->company = null;
    }
}
