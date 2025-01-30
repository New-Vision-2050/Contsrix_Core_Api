<?php

declare(strict_types=1);

namespace Modules\Company\Handlers;

use Modules\Company\Commands\ActivateCompanyCommand;
use Modules\Company\Repositories\CompanyRepository;

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
