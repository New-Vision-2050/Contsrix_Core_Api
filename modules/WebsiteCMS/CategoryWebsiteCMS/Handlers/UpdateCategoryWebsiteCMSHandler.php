<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\CategoryWebsiteCMS\Handlers;

use Modules\WebsiteCMS\CategoryWebsiteCMS\Commands\UpdateCategoryWebsiteCMSCommand;
use Modules\WebsiteCMS\CategoryWebsiteCMS\Repositories\CategoryWebsiteCMSRepository;

class UpdateCategoryWebsiteCMSHandler
{
    public function __construct(
        private CategoryWebsiteCMSRepository $repository,
    ) {
    }

    public function handle(UpdateCategoryWebsiteCMSCommand $updateCategoryWebsiteCMSCommand)
    {
        $this->repository->updateCategoryWebsiteCMS($updateCategoryWebsiteCMSCommand->getId(), $updateCategoryWebsiteCMSCommand->toArray());
    }
}
