<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Handlers;

use Modules\ProcedureSetting\Repositories\ProcedureSettingRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteProcedureSettingHandler
{
    public function __construct(
        private ProcedureSettingRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteProcedureSetting($id);
    }
}
