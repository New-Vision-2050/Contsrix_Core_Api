<?php

declare(strict_types=1);

namespace Modules\Shared\Currency\Handlers;

use Modules\Shared\Currency\Repositories\CurrencyRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCurrencyHandler
{
    public function __construct(
        private CurrencyRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCurrency($id);
    }
}
