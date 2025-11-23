<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Page\Services\Website;

use Modules\Ecommerce\Page\Models\Page;
use Modules\Ecommerce\Page\Repositories\PageRepository;
use Ramsey\Uuid\UuidInterface;

class PageCRUDWebsiteService
{
    public function __construct(
        private PageRepository $repository,
    ) {
    }

    public function getByType(string $type): ?Page
    {
        return $this->repository->getByType($type);
    }
}

