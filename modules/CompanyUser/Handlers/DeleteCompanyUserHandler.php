<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Handlers;

use Modules\CompanyUser\Repositories\CompanyUserRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyUserHandler
{
    public function __construct(
        private CompanyUserRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCompanyUser($id);
    }
}
