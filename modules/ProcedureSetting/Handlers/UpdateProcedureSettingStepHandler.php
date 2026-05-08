<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Handlers;

use Modules\ProcedureSetting\Commands\UpdateProcedureSettingStepCommand;
use Modules\ProcedureSetting\Repositories\ProcedureSettingStepRepository;

class UpdateProcedureSettingStepHandler
{
    public function __construct(
        private ProcedureSettingStepRepository $repository,
    ) {
    }

    public function handle(UpdateProcedureSettingStepCommand $command): void
    {
        $this->repository->updateProcedureSettingStep($command->getId(), $command->toArray());
    }
}
