<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers\CompanyProfile;

use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateCompanyLegalDataCommand;
use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyLegalDataRepository;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\CompanyCore\Requests\CompanyProfile\UpdateCompanyLegalDataRequest;
use Modules\Company\ManagementHierarchy\Repositories\ManagementHierarchyRepository;

class UpdateCompanyLegalDataHandler
{
    public function __construct(
        private CompanyLegalDataRepository $repository,
    )
    {
    }

    public function handle(UpdateCompanyLegalDataCommand $updateCompanyLegalDataCommand)
    {

        return $this->repository->updateCompanyLegalData($updateCompanyLegalDataCommand->getId(), $updateCompanyLegalDataCommand->toArray(), $updateCompanyLegalDataCommand->getFile());

    }
}
