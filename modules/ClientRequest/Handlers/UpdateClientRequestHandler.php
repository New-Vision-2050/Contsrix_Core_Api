<?php

declare(strict_types=1);

namespace Modules\ClientRequest\Handlers;

use Modules\ClientRequest\Commands\UpdateClientRequestCommand;
use Modules\ClientRequest\Repositories\ClientRequestRepository;

class UpdateClientRequestHandler
{
    public function __construct(
        private ClientRequestRepository $repository,
    ) {
    }

    public function handle(UpdateClientRequestCommand $updateClientRequestCommand)
    {
        $this->repository->updateClientRequest($updateClientRequestCommand->getId(), $updateClientRequestCommand->toArray());
    }
}
