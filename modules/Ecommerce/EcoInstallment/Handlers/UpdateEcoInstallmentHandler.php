<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Handlers;

use Modules\Ecommerce\EcoInstallment\Commands\UpdateEcoInstallmentCommand;
use Modules\Ecommerce\EcoInstallment\Repositories\EcoInstallmentRepository;

class UpdateEcoInstallmentHandler
{
    public function __construct(
        private EcoInstallmentRepository $repository,
    ) {
    }

    public function handle(UpdateEcoInstallmentCommand $updateEcoInstallmentCommand)
    {
        $this->repository->updateEcoInstallment($updateEcoInstallmentCommand->getId(), $updateEcoInstallmentCommand->toArray());
    }
}
