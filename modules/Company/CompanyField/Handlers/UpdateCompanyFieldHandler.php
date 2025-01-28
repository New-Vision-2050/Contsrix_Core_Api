<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Handlers;

use Modules\Company\CompanyField\Commands\UpdateCompanyFieldCommand;
use Modules\Company\CompanyField\Repositories\CompanyFieldRepository;

class UpdateCompanyFieldHandler
{
    public function __construct(
        private CompanyFieldRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyFieldCommand $updateCompanyFieldCommand)
    {
        $this->repository->updateCompanyField($updateCompanyFieldCommand->getId(), $updateCompanyFieldCommand->toArray());
    }
}
