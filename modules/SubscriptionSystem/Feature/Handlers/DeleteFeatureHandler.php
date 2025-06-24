<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Feature\Handlers;

use Modules\SubscriptionSystem\Feature\Repositories\FeatureRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteFeatureHandler
{
    public function __construct(
        private FeatureRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteFeature($id);
    }
}
