<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Handlers;

use Modules\Leave\LeavePolicyCategory\Repositories\LeavePolicyCategoryRepository;
use Modules\Leave\LeaveRequest\Services\UserLeavePolicyService;
use Ramsey\Uuid\UuidInterface;

class DeleteLeavePolicyCategoryHandler
{
    public function __construct(
        private LeavePolicyCategoryRepository $repository,
        private UserLeavePolicyService $userLeavePolicyService,
    ) {
    }

    public function handle(UuidInterface $id): void
    {
        $userIds = $this->userLeavePolicyService->userIdsForCategories([$id->toString()]);

        $this->repository->deleteLeavePolicyCategory($id);

        $this->userLeavePolicyService->resyncUsersFromContracts($userIds, (string) auth()->id());
    }
}
