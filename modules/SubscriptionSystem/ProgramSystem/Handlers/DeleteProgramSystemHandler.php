<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\ProgramSystem\Handlers;

use Modules\SubscriptionSystem\ProgramSystem\Repositories\ProgramSystemRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteProgramSystemHandler
{
    public function __construct(
        private ProgramSystemRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteProgramSystem($id);
    }
}
