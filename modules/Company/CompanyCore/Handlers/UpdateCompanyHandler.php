<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers;

use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;

class UpdateCompanyHandler
{
    public function __construct(
        private CompanyRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyCommand $updateCompanyCommand)
    {
        $this->repository->updateCompany($updateCompanyCommand->getId(), $updateCompanyCommand->toArray());
    }
}
