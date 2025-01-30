<?php

declare(strict_types=1);

namespace Modules\Company\Handlers;

use Modules\Company\Commands\UpdateCompanyCommand;
use Modules\Company\Repositories\CompanyRepository;

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
