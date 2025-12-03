<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Handlers;

use Modules\WebsiteCMS\WebsiteAddress\Repositories\WebsiteAddressRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteAddressHandler
{
    public function __construct(
        private WebsiteAddressRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteAddress($id);
    }
}
