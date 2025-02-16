<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use App\Exceptions\CustomException;
use Modules\Company\CompanyCore\Events\CompaniesDeleted;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Illuminate\Support\Facades\Log;

class CompanyCheckActivityService
{
    protected CompanyRepository $companyRepository;

    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    public function handle($companyId = null)
    {
        $companyIds = $this->companyRepository->getInactiveCompanyIds(24, $companyId);

        if ($companyIds->isEmpty()) {
            return;
        }

        $deletedCount = $this->companyRepository->deleteCompaniesByIds($companyIds->toArray());

        if ($deletedCount > 0) {
            event(new CompaniesDeleted($companyIds));
        }

    }
}
