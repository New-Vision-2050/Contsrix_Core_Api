<?php

declare(strict_types=1);

namespace Modules\Audit\Handlers;

use Modules\Audit\Commands\UpdateAuditCommand;
use Modules\Audit\Repositories\AuditRepository;

class UpdateAuditHandler
{
    public function __construct(
        private AuditRepository $repository,
    ) {
    }

    public function handle(UpdateAuditCommand $updateAuditCommand)
    {
        $this->repository->updateAudit($updateAuditCommand->getId(), $updateAuditCommand->toArray());
    }
}
