<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Handlers;

use Modules\Shared\Installment\Commands\UpdateInstallmentCommand;
use Modules\Shared\Installment\Repositories\InstallmentRepository;

class UpdateInstallmentHandler
{
    public function __construct(
        private InstallmentRepository $repository,
    ) {
    }

    public function handle(UpdateInstallmentCommand $updateInstallmentCommand)
    {
        $this->repository->updateInstallment($updateInstallmentCommand->getId(), $updateInstallmentCommand->toArray());
    }
}
