<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoPayment\Handlers;

use Modules\Ecommerce\EcoPayment\Repositories\EcoPaymentRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoPaymentHandler
{
    public function __construct(
        private EcoPaymentRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoPayment($id);
    }
}
