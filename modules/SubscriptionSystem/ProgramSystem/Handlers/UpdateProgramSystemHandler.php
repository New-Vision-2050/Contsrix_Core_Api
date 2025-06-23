<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Handlers;

use Modules\SubscriptionSystem\ProgramSystem\Commands\UpdateProgramSystemCommand;
use Modules\SubscriptionSystem\ProgramSystem\Repositories\ProgramSystemRepository;

class UpdateProgramSystemHandler
{
    public function __construct(
        private ProgramSystemRepository $repository,
    ) {
    }

    public function handle(UpdateProgramSystemCommand $updateProgramSystemCommand)
    {
        $this->repository->updateProgramSystem($updateProgramSystemCommand->getId(), $updateProgramSystemCommand->toArray());
    }
}
