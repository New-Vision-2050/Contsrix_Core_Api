<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Handlers;

use Modules\ClientRequest\Repositories\ClientRequestRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteClientRequestHandler
{
    public function __construct(
        private ClientRequestRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteClientRequest($id);
    }
}
