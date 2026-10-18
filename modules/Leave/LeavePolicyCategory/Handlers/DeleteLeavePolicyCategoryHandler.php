<?php

declare(strict_types=1);

namespace Modules\Leave\LeavePolicyCategory\Handlers;

use Modules\Leave\LeavePolicyCategory\Repositories\LeavePolicyCategoryRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteLeavePolicyCategoryHandler
{
    public function __construct(
        private LeavePolicyCategoryRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id): void
    {
        $this->repository->deleteLeavePolicyCategory($id);
    }
}
