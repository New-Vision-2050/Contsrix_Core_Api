<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Handlers;

use Modules\Ecommerce\EcoBankAccount\Commands\UpdateEcoBankAccountCommand;
use Modules\Ecommerce\EcoBankAccount\Repositories\EcoBankAccountRepository;

class UpdateEcoBankAccountHandler
{
    public function __construct(
        private EcoBankAccountRepository $repository,
    ) {
    }

    public function handle(UpdateEcoBankAccountCommand $updateEcoBankAccountCommand)
    {
        $this->repository->updateEcoBankAccount($updateEcoBankAccountCommand->getId(), $updateEcoBankAccountCommand->toArray());
    }
}
