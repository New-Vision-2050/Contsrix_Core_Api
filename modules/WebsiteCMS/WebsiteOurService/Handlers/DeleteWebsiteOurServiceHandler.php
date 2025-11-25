<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Handlers;

use Modules\WebsiteCMS\WebsiteOurService\Repositories\WebsiteOurServiceRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteOurServiceHandler
{
    public function __construct(
        private WebsiteOurServiceRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteOurService($id);
    }
}
