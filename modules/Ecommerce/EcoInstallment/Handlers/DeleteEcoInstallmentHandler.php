<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoInstallment\Handlers;

use Modules\Ecommerce\EcoInstallment\Repositories\EcoInstallmentRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoInstallmentHandler
{
    public function __construct(
        private EcoInstallmentRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoInstallment($id);
    }
}
