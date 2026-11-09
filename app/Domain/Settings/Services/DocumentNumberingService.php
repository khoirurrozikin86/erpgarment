<?php

namespace App\Domain\Settings\Services;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\DocumentNumbering;
use Illuminate\Support\Collection;

class DocumentNumberingService
{
    public function __construct(
        protected CompanyContext $companyContext
    ) {}

    /**
     * Mengambil semua konfigurasi numbering
     * milik company yang sedang aktif.
     */
    public function all(): Collection
    {
        return DocumentNumbering::query()
            ->where('company_id', $this->companyContext->id())
            ->orderBy('id')
            ->get();
    }

    /**
     * Mengambil satu konfigurasi numbering.
     */
    public function find(int $id): DocumentNumbering
    {
        return DocumentNumbering::query()
            ->where('company_id', $this->companyContext->id())
            ->findOrFail($id);
    }

    /**
     * Update konfigurasi numbering.
     */
    public function update(
        DocumentNumbering $numbering,
        array $data
    ): DocumentNumbering {
        // Pastikan numbering milik company yang sedang aktif.
        abort_unless(
            $numbering->company_id === $this->companyContext->id(),
            403
        );

        $numbering->update($data);

        return $numbering->refresh();
    }
}
