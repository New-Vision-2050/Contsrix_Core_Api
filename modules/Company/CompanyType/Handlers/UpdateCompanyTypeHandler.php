<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Handlers;

use Modules\Company\CompanyType\Commands\UpdateCompanyTypeCommand;
use Modules\Company\CompanyType\Repositories\CompanyTypeRepository;

class UpdateCompanyTypeHandler
{
    public function __construct(
        private CompanyTypeRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyTypeCommand $updateCompanyTypeCommand)
    {
        $this->repository->updateCompanyType($updateCompanyTypeCommand->getId(), $updateCompanyTypeCommand->toArray());
    }
}
