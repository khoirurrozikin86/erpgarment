<?php

namespace App\Http\Middleware;

use App\Domain\Companies\Services\CompanyContext;
use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    public function __construct(
        private CompanyContext $companyContext
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $company = Company::query()
            ->where('is_active', true)
            ->first();

        if ($company) {
            $this->companyContext->set($company);
        }

        return $next($request);
    }
}
