<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Handlers;

use Modules\WebsiteCMS\WebsiteTermAndCondition\Repositories\WebsiteTermAndConditionRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteWebsiteTermAndConditionHandler
{
    public function __construct(
        private WebsiteTermAndConditionRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteWebsiteTermAndCondition($id);
    }
}
