<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Handlers;

use Modules\Company\CompanyRegistrationType\Commands\UpdateCompanyRegistrationTypeCommand;
use Modules\Company\CompanyRegistrationType\Repositories\CompanyRegistrationTypeRepository;

class UpdateCompanyRegistrationTypeHandler
{
    public function __construct(
        private CompanyRegistrationTypeRepository $repository,
    ) {
    }

    public function handle(UpdateCompanyRegistrationTypeCommand $updateCompanyRegistrationTypeCommand)
    {
        $this->repository->updateCompanyRegistrationType($updateCompanyRegistrationTypeCommand->getId(), $updateCompanyRegistrationTypeCommand->toArray());
    }
}
