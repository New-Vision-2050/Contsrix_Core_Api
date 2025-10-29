<?php

declare(strict_types=1);

namespace Modules\Ecommerce\FeatureDeal\Handlers;

use Modules\Ecommerce\FeatureDeal\Repositories\FeatureDealRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteFeatureDealHandler
{
    public function __construct(
        private FeatureDealRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteFeatureDeal($id);
    }
}
