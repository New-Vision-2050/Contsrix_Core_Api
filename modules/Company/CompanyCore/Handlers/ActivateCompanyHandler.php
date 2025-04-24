<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers;

use Modules\Company\CompanyCore\Commands\ActivateCompanyCommand;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;

class ActivateCompanyHandler
{
    public function __construct(
        private CompanyRepository $repository,
    ) {
    }

    public function handle(ActivateCompanyCommand $activateCompanyCommand)
    {
        $this->repository->updateCompany($activateCompanyCommand->getId(), $activateCompanyCommand->toArray());
    }
}
