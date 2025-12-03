<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteTermAndCondition\Handlers;

use Modules\WebsiteCMS\WebsiteTermAndCondition\Commands\UpdateWebsiteTermAndConditionCommand;
use Modules\WebsiteCMS\WebsiteTermAndCondition\Repositories\WebsiteTermAndConditionRepository;

class UpdateWebsiteTermAndConditionHandler
{
    public function __construct(
        private WebsiteTermAndConditionRepository $repository,
    ) {
    }

    public function handle(UpdateWebsiteTermAndConditionCommand $updateWebsiteTermAndConditionCommand)
    {
        $this->repository->updateWebsiteTermAndCondition($updateWebsiteTermAndConditionCommand->getId(), $updateWebsiteTermAndConditionCommand->toArray());
    }
}
