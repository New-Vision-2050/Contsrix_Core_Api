<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Handlers;

use Modules\Shared\SalaryType\Repositories\SalaryTypeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteSalaryTypeHandler
{
    public function __construct(
        private SalaryTypeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteSalaryType($id);
    }
}
