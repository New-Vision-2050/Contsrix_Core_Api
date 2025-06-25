<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Handlers;

use Modules\SubscriptionSystem\Feature\Commands\UpdateFeatureCommand;
use Modules\SubscriptionSystem\Feature\Repositories\FeatureRepository;

class UpdateFeatureHandler
{
    public function __construct(
        private FeatureRepository $repository,
    ) {
    }

    public function handle(UpdateFeatureCommand $updateFeatureCommand)
    {
        $this->repository->updateFeature($updateFeatureCommand->getId(), $updateFeatureCommand->toArray());
    }
}
