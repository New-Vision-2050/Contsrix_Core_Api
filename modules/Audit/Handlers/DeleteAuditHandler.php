<?php

declare(strict_types=1);

namespace Modules\Audit\Handlers;

use Modules\Audit\Repositories\AuditRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteAuditHandler
{
    public function __construct(
        private AuditRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteAudit($id);
    }
}
