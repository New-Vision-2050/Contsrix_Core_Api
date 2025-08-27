<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Handlers;

use Modules\Ecommerce\EcoClient\Commands\UpdateEcoClientCommand;
use Modules\Ecommerce\EcoClient\Repositories\EcoClientRepository;

class UpdateEcoClientHandler
{
    public function __construct(
        private EcoClientRepository $repository,
    ) {
    }

    public function handle(UpdateEcoClientCommand $updateEcoClientCommand)
    {
        $this->repository->updateEcoClient($updateEcoClientCommand->getId(), $updateEcoClientCommand->toArray());
    }
}
