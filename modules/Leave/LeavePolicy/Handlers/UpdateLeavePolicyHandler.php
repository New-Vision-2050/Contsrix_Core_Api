<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Handlers;

use Modules\Leave\LeavePolicy\Commands\UpdateLeavePolicyCommand;
use Modules\Leave\LeavePolicy\Repositories\LeavePolicyRepository;

class UpdateLeavePolicyHandler
{
    public function __construct(
        private LeavePolicyRepository $repository,
    ) {
    }

    public function handle(UpdateLeavePolicyCommand $updateLeavePolicyCommand)
    {
        $this->repository->updateLeavePolicy($updateLeavePolicyCommand->getId(), $updateLeavePolicyCommand->toArray());
    }
}
