<?php

declare(strict_types=1);

namespace Modules\UserInfo\Social\Handlers;

use Modules\UserInfo\Social\Commands\UpdateSocialCommand;
use Modules\UserInfo\Social\Repositories\SocialRepository;

class UpdateSocialHandler
{
    public function __construct(
        private SocialRepository $repository,
    ) {
    }

    public function handle(UpdateSocialCommand $updateSocialCommand)
    {
        $this->repository->updateSocial($updateSocialCommand->companyUserId, $updateSocialCommand->toArray());
    }
}
