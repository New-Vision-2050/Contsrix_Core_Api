<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteAddress\Handlers;

use Modules\WebsiteCMS\WebsiteAddress\Commands\UpdateWebsiteAddressCommand;
use Modules\WebsiteCMS\WebsiteAddress\Repositories\WebsiteAddressRepository;

class UpdateWebsiteAddressHandler
{
    public function __construct(
        private WebsiteAddressRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteAddressCommand $updateWebsiteAddressCommand)
    {
        $this->repository->updateWebsiteAddress($updateWebsiteAddressCommand->getId(), $updateWebsiteAddressCommand->toArray());
    }
}
