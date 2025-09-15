<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBusinessActivity\Handlers;

use Modules\Ecommerce\EcoBusinessActivity\Commands\UpdateEcoBusinessActivityCommand;
use Modules\Ecommerce\EcoBusinessActivity\Repositories\EcoBusinessActivityRepository;

class UpdateEcoBusinessActivityHandler
{
    public function __construct(
        private EcoBusinessActivityRepository $repository,
    ) {
    }

    public function handle(UpdateEcoBusinessActivityCommand $updateEcoBusinessActivityCommand)
    {
        $this->repository->updateEcoBusinessActivity($updateEcoBusinessActivityCommand->getId(), $updateEcoBusinessActivityCommand->toArray());
    }
}
