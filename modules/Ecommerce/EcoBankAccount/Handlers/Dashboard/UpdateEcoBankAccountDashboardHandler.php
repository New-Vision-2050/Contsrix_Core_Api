<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBankAccount\Handlers\Dashboard;

use Modules\Ecommerce\EcoBankAccount\Commands\UpdateEcoBankAccountCommand;
use Modules\Ecommerce\EcoBankAccount\Repositories\EcoBankAccountRepository;

class UpdateEcoBankAccountDashboardHandler
{
    public function __construct(
        private EcoBankAccountRepository $repository,
    ) {
    }

    public function handle(UpdateEcoBankAccountDashboardCommand $updateEcoBankAccountCommand)
    {
        $this->repository->updateEcoBankAccount($updateEcoBankAccountCommand->getId(), $updateEcoBankAccountCommand->toArray());
    }
}
