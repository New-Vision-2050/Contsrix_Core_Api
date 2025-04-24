<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Handlers;

use Modules\Company\CompanyField\Repositories\CompanyFieldRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyFieldHandler
{
    public function __construct(
        private CompanyFieldRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCompanyField($id);
    }
}
