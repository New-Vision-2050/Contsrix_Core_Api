<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Handlers;

use Modules\Shared\Currency\Commands\UpdateCurrencyCommand;
use Modules\Shared\Currency\Repositories\CurrencyRepository;

class UpdateCurrencyHandler
{
    public function __construct(
        private CurrencyRepository $repository,
    ) {
    }

    public function handle(UpdateCurrencyCommand $updateCurrencyCommand)
    {
        $this->repository->updateCurrency($updateCurrencyCommand->getId(), $updateCurrencyCommand->toArray());
    }
}
