<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Handlers;

use Modules\Subscription\CompanyAccessProgram\Commands\UpdateCompanyAccessProgramCommand;
use Modules\Subscription\CompanyAccessProgram\Repositories\CompanyAccessProgramRepository;

class UpdateCompanyAccessProgramHandler
{
    public function __construct(
        private CompanyAccessProgramRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyAccessProgramCommand $updateCompanyAccessProgramCommand)
    {
        $this->repository->updateCompanyAccessProgram($updateCompanyAccessProgramCommand->getId(), $updateCompanyAccessProgramCommand->toArray());
    }
}
