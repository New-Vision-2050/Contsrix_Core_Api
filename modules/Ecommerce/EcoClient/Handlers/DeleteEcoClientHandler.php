<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoClient\Handlers;

use Modules\Ecommerce\EcoClient\Repositories\EcoClientRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteEcoClientHandler
{
    public function __construct(
        private EcoClientRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteEcoClient($id);
    }
}
