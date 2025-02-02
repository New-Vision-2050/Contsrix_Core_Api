<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Handlers;

use Modules\Company\CompanyType\Repositories\CompanyTypeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyTypeHandler
{
    public function __construct(
        private CompanyTypeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCompanyType($id);
    }
}
