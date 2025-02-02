<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Handlers;

use Modules\Company\CompanyRegistrationForm\Repositories\CompanyRegistrationFormRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCompanyRegistrationFormHandler
{
    public function __construct(
        private CompanyRegistrationFormRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCompanyRegistrationForm($id);
    }
}
