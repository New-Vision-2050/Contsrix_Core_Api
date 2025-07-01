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
        $package = $this->repository->findOneOrFail($updatePackageCommand->getId());

        $packageData = $updatePackageCommand->toPackageArray();
        if (!empty($packageData)) {
            $package->update($packageData);
        }
        
        if ($updatePackageCommand->businessTypeIds !== null) {
            $package->businessTypes()->sync($updatePackageCommand->businessTypeIds);
        }
        if ($updatePackageCommand->countryIds !== null) {
            $package->countries()->sync($updatePackageCommand->countryIds);
        }
        if ($updatePackageCommand->programSystemIds !== null) {
            $package->programSystems()->sync($updatePackageCommand->programSystemIds);
        }    }
}
