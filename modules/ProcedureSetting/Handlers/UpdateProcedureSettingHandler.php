<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Handlers;

use Modules\ProcedureSetting\Commands\UpdateProcedureSettingCommand;
use Modules\ProcedureSetting\Repositories\ProcedureSettingRepository;

class UpdateProcedureSettingHandler
{
    public function __construct(
        private ProcedureSettingRepository $repository,
    ) {
    }

    public function handle(UpdateProcedureSettingCommand $updateProcedureSettingCommand)
    {
        $this->repository->updateProcedureSetting($updateProcedureSettingCommand->getId(), $updateProcedureSettingCommand->toArray());
    }
}
