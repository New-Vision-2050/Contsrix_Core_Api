<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Handlers;

use Modules\WebsiteCMS\CategoryWebsiteCMS\Repositories\CategoryWebsiteCMSRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteCategoryWebsiteCMSHandler
{
    public function __construct(
        private CategoryWebsiteCMSRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteCategoryWebsiteCMS($id);
    }
}
