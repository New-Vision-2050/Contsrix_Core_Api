<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Handlers\Dashboard;

use Modules\Ecommerce\EcoBankAccount\Repositories\EcoBankAccountRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoBankAccountDashboardHandler
{
    public function __construct(
        private EcoBankAccountRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoBankAccount($id);
    }
}
