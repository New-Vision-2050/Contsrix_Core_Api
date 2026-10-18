<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Handlers;

use Modules\Leave\LeavePolicyCategory\Commands\UpdateLeavePolicyCategoryCommand;
use Modules\Leave\LeavePolicyCategory\Repositories\LeavePolicyCategoryRepository;

class UpdateLeavePolicyCategoryHandler
{
    public function __construct(
        private LeavePolicyCategoryRepository $repository,
    ) {
    }

    public function handle(UpdateLeavePolicyCategoryCommand $command): void
    {
        $this->repository->updateLeavePolicyCategory($command->getId(), $command->toArray());
    }
}
