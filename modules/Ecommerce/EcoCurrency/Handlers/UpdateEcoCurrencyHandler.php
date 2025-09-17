<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Handlers;

use Modules\Ecommerce\EcoCurrency\Commands\UpdateEcoCurrencyCommand;
use Modules\Ecommerce\EcoCurrency\Repositories\EcoCurrencyRepository;

class UpdateEcoCurrencyHandler
{
    public function __construct(
        private EcoCurrencyRepository $repository,
    ) {
    }

    public function handle(UpdateEcoCurrencyCommand $updateEcoCurrencyCommand)
    {
        $this->repository->updateEcoCurrency($updateEcoCurrencyCommand->getId(), $updateEcoCurrencyCommand->toArray());
    }
}
