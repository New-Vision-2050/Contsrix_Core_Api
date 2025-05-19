<?php

declare(strict_types=1);

namespace Modules\SubEntity\Handlers;

use Modules\SubEntity\Commands\UpdateSuperEntityAttributesConfigCommand;
use Modules\SubEntity\Repositories\SuperEntityRepository;

class UpdateSuperEntityAttributesConfigHandler
{
    public function __construct(
        private SuperEntityRepository $repository,
    ) {
    }

    public function handle(UpdateSuperEntityAttributesConfigCommand $updateSuperEntityAttributesConfigCommand)
    {
        $this->repository->setAttributesConfig($updateSuperEntityAttributesConfigCommand->getId(), $updateSuperEntityAttributesConfigCommand->toArray());
    }
}
