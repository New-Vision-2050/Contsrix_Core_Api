<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers\CompanyProfile;

use Modules\Company\CompanyCore\Commands\CompanyProfile\UpdateOfficialCompanyDataCommand;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;

class UpdateOfficialCompanyDataHandler
{
    public function __construct(
        private CompanyRepository $repository,
    ) {
    }

    public function handle(UpdateOfficialCompanyDataCommand $updateOfficialCompanyDataCommand)
    {
        $this->repository->updateCompany($updateOfficialCompanyDataCommand->getId(), $updateOfficialCompanyDataCommand->toArray());
    }
}
