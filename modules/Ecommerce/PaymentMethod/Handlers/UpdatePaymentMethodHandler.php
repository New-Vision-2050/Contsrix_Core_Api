<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Handlers;

use Modules\Ecommerce\PaymentMethod\Commands\UpdatePaymentMethodCommand;
use Modules\Ecommerce\PaymentMethod\Repositories\PaymentMethodRepository;

class UpdatePaymentMethodHandler
{
    public function __construct(
        private PaymentMethodRepository $repository,
    ) {
    }

    public function handle(UpdatePaymentMethodCommand $updatePaymentMethodCommand)
    {
        $this->repository->updatePaymentMethod($updatePaymentMethodCommand->getId(), $updatePaymentMethodCommand->toArray());
    }
}
