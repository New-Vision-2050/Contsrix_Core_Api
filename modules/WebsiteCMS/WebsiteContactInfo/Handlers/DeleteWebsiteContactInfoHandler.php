<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactInfo\Handlers;

use Modules\WebsiteCMS\WebsiteContactInfo\Repositories\WebsiteContactInfoRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteContactInfoHandler
{
    public function __construct(
        private WebsiteContactInfoRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteContactInfo($id);
    }
}
