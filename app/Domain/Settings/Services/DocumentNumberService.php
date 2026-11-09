<?php

namespace App\Domain\Settings\Services;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\DocumentNumbering;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function __construct(
        protected CompanyContext $companyContext
    ) {}

    public function generate(string $documentType): string
    {
        return DB::transaction(function () use ($documentType) {

            $companyId = $this->companyContext->id();

            $numbering = DocumentNumbering::query()
                ->where('company_id', $companyId)
                ->where('document_type', $documentType)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();

            /*
             * Reset numbering berdasarkan periode.
             *
             * Monthly:
             * 2026/09 -> 00001, 00002, ...
             * 2026/10 -> 00001
             *
             * Yearly:
             * 2026 -> 00001, 00002, ...
             * 2027 -> 00001
             *
             * Never:
             * Nomor terus berlanjut.
             */

            $shouldReset = false;

            if ($numbering->reset_period === 'monthly') {

                $currentPeriod = $now->format('Y-m');

                $lastPeriod = $numbering->last_reset_at
                    ? $numbering->last_reset_at->format('Y-m')
                    : null;

                if ($currentPeriod !== $lastPeriod) {
                    $shouldReset = true;
                }
            } elseif ($numbering->reset_period === 'yearly') {

                $currentPeriod = $now->format('Y');

                $lastPeriod = $numbering->last_reset_at
                    ? $numbering->last_reset_at->format('Y')
                    : null;

                if ($currentPeriod !== $lastPeriod) {
                    $shouldReset = true;
                }
            }

            if ($shouldReset) {
                $numbering->next_number = 1;
                $numbering->last_reset_at = $now;
                $numbering->save();
            }

            $number = $numbering->next_number;

            $formattedNumber = str_pad(
                (string) $number,
                $numbering->number_length,
                '0',
                STR_PAD_LEFT
            );

            $documentNumber = str_replace(
                [
                    '{PREFIX}',
                    '{YYYY}',
                    '{YY}',
                    '{MM}',
                    '{DD}',
                    '{NUMBER}',
                ],
                [
                    $numbering->prefix,
                    $now->format('Y'),
                    $now->format('y'),
                    $now->format('m'),
                    $now->format('d'),
                    $formattedNumber,
                ],
                $numbering->format
            );

            $numbering->increment('next_number');

            return $documentNumber;
        });
    }
}
