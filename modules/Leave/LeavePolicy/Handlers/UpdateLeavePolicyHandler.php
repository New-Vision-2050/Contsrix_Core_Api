<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicy\Handlers;

use Modules\Leave\LeavePolicy\Commands\UpdateLeavePolicyCommand;
use Modules\Leave\LeavePolicy\Repositories\LeavePolicyRepository;
use Modules\Leave\LeaveRequest\Services\UserLeavePolicyService;

class UpdateLeavePolicyHandler
{
    public function __construct(
        private LeavePolicyRepository $repository,
        private UserLeavePolicyService $userLeavePolicyService,
    ) {
    }

    public function handle(UpdateLeavePolicyCommand $updateLeavePolicyCommand)
    {
        $oldCategoryId = $this->repository->getLeavePolicy($updateLeavePolicyCommand->getId())->leave_category_id;

        $result = $this->repository->updateLeavePolicy($updateLeavePolicyCommand->getId(), $updateLeavePolicyCommand->toArray());

        $newCategoryId = $updateLeavePolicyCommand->getLeaveCategoryId() ?? $oldCategoryId;

        if ($newCategoryId !== $oldCategoryId) {
            $this->userLeavePolicyService->resyncUsersOfCategories([$oldCategoryId, $newCategoryId], (string) auth()->id());
        }

        return $result;
    }
}
