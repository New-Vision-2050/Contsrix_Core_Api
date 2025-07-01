<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Handlers;

use Modules\Subscription\CompanyAccessProgram\Repositories\CompanyAccessProgramRepository;
use Modules\Subscription\CompanyAccessProgram\Commands\UpdateCompanyAccessProgramStatusCommand;

class UpdateCompanyAccessProgramStatusHandler
{
    public function __construct(
        private CompanyAccessProgramRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyAccessProgramStatusCommand $updateCompanyAccessProgramStatusCommand)
    {
        $this->repository->updateCompanyAccessProgram($updateCompanyAccessProgramStatusCommand->getId(), $updateCompanyAccessProgramStatusCommand->toArray());
    }
}
