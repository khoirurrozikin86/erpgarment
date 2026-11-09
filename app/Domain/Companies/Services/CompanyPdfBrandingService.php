<?php

namespace App\Domain\Companies\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

class CompanyPdfBrandingService
{
    public function __construct(private CompanyContext $companyContext) {}

    /** @return array{company: ?\App\Models\Company, companyLogoDataUri: ?string} */
    public function data(): array
    {
        if (!$this->companyContext->has()) {
            return ['company' => null, 'companyLogoDataUri' => null];
        }

        $company = $this->companyContext->get();
        $logoDataUri = null;

        if ($company->logo && Storage::disk('public')->exists($company->logo)) {
            try {
                $logoDataUri = ImageManager::gd()
                    ->read(Storage::disk('public')->get($company->logo))
                    ->toPng()
                    ->toDataUri();
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return [
            'company' => $company,
            'companyLogoDataUri' => $logoDataUri,
        ];
    }
}
