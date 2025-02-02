<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Handlers;

use Modules\Company\CompanyRegistrationForm\Commands\UpdateCompanyRegistrationFormCommand;
use Modules\Company\CompanyRegistrationForm\Repositories\CompanyRegistrationFormRepository;

class UpdateCompanyRegistrationFormHandler
{
    public function __construct(
        private CompanyRegistrationFormRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyRegistrationFormCommand $updateCompanyRegistrationFormCommand)
    {
        $this->repository->updateCompanyRegistrationForm($updateCompanyRegistrationFormCommand->getId(), $updateCompanyRegistrationFormCommand->toArray());
    }
}
