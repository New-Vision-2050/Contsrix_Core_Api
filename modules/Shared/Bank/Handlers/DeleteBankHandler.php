<?php

declare(strict_types=1);

namespace Modules\Shared\Bank\Handlers;

use Modules\Shared\Bank\Repositories\BankRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteBankHandler
{
    public function __construct(
        private BankRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteBank($id);
    }
}
