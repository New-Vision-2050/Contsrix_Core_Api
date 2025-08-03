<?php

declare(strict_types=1);

namespace Modules\Subscription\Package\Handlers;

use Modules\Subscription\Package\Repositories\PackageRepository;
use Modules\Subscription\Package\Commands\UpdatePackageStatusCommand;

class UpdatePackageStatusHandler
{
    public function __construct(
        private PackageRepository $repository,
    ) {
    }

    public function handle(UpdatePackageStatusCommand $updatePackageCommand)
    {
        $this->repository->updatePackage($updatePackageCommand->getId(), $updatePackageCommand->toArray());
    }
}
