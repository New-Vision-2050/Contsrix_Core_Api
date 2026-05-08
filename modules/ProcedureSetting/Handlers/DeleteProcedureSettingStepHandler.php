<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Handlers;

use Modules\ProcedureSetting\Repositories\ProcedureSettingStepRepository;

class DeleteProcedureSettingStepHandler
{
    public function __construct(
        private ProcedureSettingStepRepository $repository,
    ) {
    }

    public function handle(int $id): void
    {
        $this->repository->deleteProcedureSettingStep($id);
    }
}
