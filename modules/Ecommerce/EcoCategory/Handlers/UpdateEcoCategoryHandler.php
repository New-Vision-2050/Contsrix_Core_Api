<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Handlers;

use Modules\Ecommerce\EcoCategory\Commands\UpdateEcoCategoryCommand;
use Modules\Ecommerce\EcoCategory\Repositories\EcoCategoryRepository;

class UpdateEcoCategoryHandler
{
    public function __construct(
        private EcoCategoryRepository $repository,
    ) {
    }

    public function handle(UpdateEcoCategoryCommand $updateEcoCategoryCommand)
    {
        $this->repository->updateEcoCategory($updateEcoCategoryCommand->getId(), $updateEcoCategoryCommand->toArray());
    }
}
