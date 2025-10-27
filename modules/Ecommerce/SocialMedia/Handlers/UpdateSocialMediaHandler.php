<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Handlers;

use Modules\Ecommerce\SocialMedia\Commands\UpdateSocialMediaCommand;
use Modules\Ecommerce\SocialMedia\Repositories\SocialMediaRepository;

class UpdateSocialMediaHandler
{
    public function __construct(
        private SocialMediaRepository $repository,
    ) {
    }

    public function handle(UpdateSocialMediaCommand $updateSocialMediaCommand)
    {
        $this->repository->updateSocialMedia($updateSocialMediaCommand->getId(), $updateSocialMediaCommand->toArray());
    }
}
