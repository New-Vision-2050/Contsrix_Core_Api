<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Handlers;

use Modules\Ecommerce\EcoPayment\Commands\UpdateEcoPaymentCommand;
use Modules\Ecommerce\EcoPayment\Repositories\EcoPaymentRepository;

class UpdateEcoPaymentHandler
{
    public function __construct(
        private EcoPaymentRepository $repository,
    ) {
    }

    public function handle(UpdateEcoPaymentCommand $updateEcoPaymentCommand)
    {
        $this->repository->updateEcoPayment($updateEcoPaymentCommand->getId(), $updateEcoPaymentCommand->toArray());
    }
}
