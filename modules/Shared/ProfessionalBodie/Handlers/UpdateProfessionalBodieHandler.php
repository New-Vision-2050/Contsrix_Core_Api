<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Handlers;

use Modules\Shared\ProfessionalBodie\Commands\UpdateProfessionalBodieCommand;
use Modules\Shared\ProfessionalBodie\Repositories\ProfessionalBodieRepository;

class UpdateProfessionalBodieHandler
{
    public function __construct(
        private ProfessionalBodieRepository $repository,
    ) {
    }

    public function handle(UpdateProfessionalBodieCommand $updateProfessionalBodieCommand)
    {
        $this->repository->updateProfessionalBodie($updateProfessionalBodieCommand->getId(), $updateProfessionalBodieCommand->toArray());
    }
}
