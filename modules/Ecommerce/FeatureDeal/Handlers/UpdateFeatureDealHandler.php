<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Handlers;

use Modules\Ecommerce\FeatureDeal\Commands\UpdateFeatureDealCommand;
use Modules\Ecommerce\FeatureDeal\Repositories\FeatureDealRepository;

class UpdateFeatureDealHandler
{
    public function __construct(
        private FeatureDealRepository $repository,
    ) {
    }

    public function handle(UpdateFeatureDealCommand $updateFeatureDealCommand)
    {
        $this->repository->updateFeatureDeal($updateFeatureDealCommand->getId(), $updateFeatureDealCommand->toArray());
    }
}
