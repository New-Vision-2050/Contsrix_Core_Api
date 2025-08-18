<?php

declare(strict_types=1);

namespace Modules\Leave\LeaveType\Handlers;

use Modules\Leave\LeaveType\Commands\UpdateLeaveTypeCommand;
use Modules\Leave\LeaveType\Repositories\LeaveTypeRepository;

class UpdateLeaveTypeHandler
{
    public function __construct(
        private LeaveTypeRepository $repository,
    ) {
    }

    public function handle(UpdateLeaveTypeCommand $updateLeaveTypeCommand)
    {
        $this->repository->updateLeaveType($updateLeaveTypeCommand->getId(), $updateLeaveTypeCommand->toArray());
    }
}
