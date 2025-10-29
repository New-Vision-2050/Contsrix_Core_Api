<?php

declare(strict_types=1);

namespace Modules\Shared\PaymentMethodData\Handlers;

use Modules\Shared\PaymentMethodData\Repositories\PaymentMethodDataRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePaymentMethodDataHandler
{
    public function __construct(
        private PaymentMethodDataRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePaymentMethodData($id);
    }
}
