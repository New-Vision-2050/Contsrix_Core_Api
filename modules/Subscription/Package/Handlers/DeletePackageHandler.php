<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Handlers;

use Modules\Subscription\Package\Repositories\PackageRepository;
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
