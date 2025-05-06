<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Handlers\CompanyProfile;

use Modules\Company\CompanyCore\Repositories\CompanyLegalDataRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyLegalDataHandler
{
    public function __construct(
        private CompanyLegalDataRepository $repository,
    )
    {
    }

    public function handle(UuidInterface $id)
    {
        return $this->repository->delete($id);
    }
}
