<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Handlers;

use Modules\Subscription\Package\Commands\UpdatePackageCommand;
use Modules\Subscription\Package\Repositories\PackageRepository;

class UpdatePackageHandler
{
    public function __construct(
        private PackageRepository $repository,
    ) {
    }

    public function handle(UpdatePackageCommand $updatePackageCommand)
    {
        $this->repository->updatePackage($updatePackageCommand->getId(), $updatePackageCommand->toArray());
    }
}
