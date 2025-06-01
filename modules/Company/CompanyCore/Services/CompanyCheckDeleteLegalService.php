<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Modules\Company\CompanyCore\Repositories\CompanyLegalDataRepository;

class CompanyCheckDeleteLegalService
{
    public function __construct(
        private CompanyLegalDataRepository $companyLegalDataRepository,
    ) {}

    /**
     * Return true if legal data exists, false otherwise.
     */
    public function handle($companyLegalDataId): bool
    {
        $legalData = $this->companyLegalDataRepository->getCompanyLegalData($companyLegalDataId);
        return $legalData !== null;
    }
}
