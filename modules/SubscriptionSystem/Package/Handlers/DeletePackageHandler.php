<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Handlers;

use Modules\SubscriptionSystem\Package\Repositories\PackageRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePackageHandler
{
    public function __construct(
        private PackageRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePackage($id);
    }
}
