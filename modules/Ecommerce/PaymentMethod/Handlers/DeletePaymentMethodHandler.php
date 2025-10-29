<?php

declare(strict_types=1);

namespace Modules\Ecommerce\PaymentMethod\Handlers;

use Modules\Ecommerce\PaymentMethod\Repositories\PaymentMethodRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePaymentMethodHandler
{
    public function __construct(
        private PaymentMethodRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePaymentMethod($id);
    }
}
