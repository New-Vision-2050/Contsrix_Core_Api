<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Package\Handlers;

use Modules\SubscriptionSystem\Package\Commands\UpdatePackageCommand;
use Modules\SubscriptionSystem\Package\Repositories\PackageRepository;

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
