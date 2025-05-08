<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Handlers;

use Modules\Shared\SalaryType\Commands\UpdateSalaryTypeCommand;
use Modules\Shared\SalaryType\Repositories\SalaryTypeRepository;

class UpdateSalaryTypeHandler
{
    public function __construct(
        private SalaryTypeRepository $repository,
    ) {
    }

    public function handle(UpdateSalaryTypeCommand $updateSalaryTypeCommand)
    {
        $this->repository->updateSalaryType($updateSalaryTypeCommand->getId(), $updateSalaryTypeCommand->toArray());
    }
}
