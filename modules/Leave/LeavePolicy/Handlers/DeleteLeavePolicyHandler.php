<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Handlers;

use Modules\Leave\LeavePolicy\Repositories\LeavePolicyRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteLeavePolicyHandler
{
    public function __construct(
        private LeavePolicyRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteLeavePolicy($id);
    }
}
