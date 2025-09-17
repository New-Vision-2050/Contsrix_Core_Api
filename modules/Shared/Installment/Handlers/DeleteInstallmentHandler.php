<?php

declare(strict_types=1);

namespace Modules\Shared\Installment\Handlers;

use Modules\Shared\Installment\Repositories\InstallmentRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteInstallmentHandler
{
    public function __construct(
        private InstallmentRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteInstallment($id);
    }
}
