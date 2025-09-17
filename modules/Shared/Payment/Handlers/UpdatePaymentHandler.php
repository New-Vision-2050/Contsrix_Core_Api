<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Handlers;

use Modules\Shared\Payment\Commands\UpdatePaymentCommand;
use Modules\Shared\Payment\Repositories\PaymentRepository;

class UpdatePaymentHandler
{
    public function __construct(
        private PaymentRepository $repository,
    ) {
    }

    public function handle(UpdatePaymentCommand $updatePaymentCommand)
    {
        $this->repository->updatePayment($updatePaymentCommand->getId(), $updatePaymentCommand->toArray());
    }
}
