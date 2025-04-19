<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Handlers;

use Modules\Shared\Bank\Commands\UpdateBankCommand;
use Modules\Shared\Bank\Repositories\BankRepository;

class UpdateBankHandler
{
    public function __construct(
        private BankRepository $repository,
    ) {
    }

    public function handle(UpdateBankCommand $updateBankCommand)
    {
        $this->repository->updateBank($updateBankCommand->getId(), $updateBankCommand->toArray());
    }
}
