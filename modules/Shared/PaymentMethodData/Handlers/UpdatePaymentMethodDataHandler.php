<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Handlers;

use Modules\Shared\PaymentMethodData\Commands\UpdatePaymentMethodDataCommand;
use Modules\Shared\PaymentMethodData\Repositories\PaymentMethodDataRepository;

class UpdatePaymentMethodDataHandler
{
    public function __construct(
        private PaymentMethodDataRepository $repository,
    ) {
    }

    public function handle(UpdatePaymentMethodDataCommand $updatePaymentMethodDataCommand)
    {
        $this->repository->updatePaymentMethodData($updatePaymentMethodDataCommand->getId(), $updatePaymentMethodDataCommand->toArray());
    }
}
