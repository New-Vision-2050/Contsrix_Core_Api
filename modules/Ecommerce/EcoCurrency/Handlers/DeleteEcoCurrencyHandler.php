<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCurrency\Handlers;

use Modules\Ecommerce\EcoCurrency\Repositories\EcoCurrencyRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoCurrencyHandler
{
    public function __construct(
        private EcoCurrencyRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoCurrency($id);
    }
}
