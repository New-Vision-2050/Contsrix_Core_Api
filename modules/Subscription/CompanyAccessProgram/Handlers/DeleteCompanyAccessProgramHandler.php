<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Handlers;

use Modules\Subscription\CompanyAccessProgram\Repositories\CompanyAccessProgramRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyAccessProgramHandler
{
    public function __construct(
        private CompanyAccessProgramRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCompanyAccessProgram($id);
    }
}
