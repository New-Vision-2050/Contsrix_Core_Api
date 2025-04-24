<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Handlers;

use Modules\Shared\TypeAllowance\Commands\UpdateTypeAllowanceCommand;
use Modules\Shared\TypeAllowance\Repositories\TypeAllowanceRepository;

class UpdateTypeAllowanceHandler
{
    public function __construct(
        private TypeAllowanceRepository $repository,
    ) {
    }

    public function handle(UpdateTypeAllowanceCommand $updateTypeAllowanceCommand)
    {
        $this->repository->updateTypeAllowance($updateTypeAllowanceCommand->getId(), $updateTypeAllowanceCommand->toArray());
    }
}
