<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Handlers;

use Modules\Shared\SocialIcon\Commands\UpdateSocialIconCommand;
use Modules\Shared\SocialIcon\Repositories\SocialIconRepository;

class UpdateSocialIconHandler
{
    public function __construct(
        private SocialIconRepository $repository,
    ) {
    }

    public function handle(UpdateSocialIconCommand $updateSocialIconCommand)
    {
        $this->repository->updateSocialIcon($updateSocialIconCommand->getId(), $updateSocialIconCommand->toArray());
    }
}
