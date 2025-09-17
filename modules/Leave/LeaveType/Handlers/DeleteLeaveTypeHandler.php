<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Handlers;

use Modules\Leave\LeaveType\Repositories\LeaveTypeRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteLeaveTypeHandler
{
    public function __construct(
        private LeaveTypeRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteLeaveType($id);
    }
}
