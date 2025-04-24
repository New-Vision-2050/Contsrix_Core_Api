<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Handlers;

use Modules\Company\CompanyRegistrationType\Repositories\CompanyRegistrationTypeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyRegistrationTypeHandler
{
    public function __construct(
        private CompanyRegistrationTypeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCompanyRegistrationType($id);
    }
}
