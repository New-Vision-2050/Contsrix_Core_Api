<?php

declare(strict_types=1);

namespace Modules\Shared\Payment\Handlers;

use Modules\Shared\Payment\Repositories\PaymentRepository;
use Ramsey\Uuid\UuidInterface;

class DeletePaymentHandler
{
    public function __construct(
        private PaymentRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deletePayment($id);
    }
}
