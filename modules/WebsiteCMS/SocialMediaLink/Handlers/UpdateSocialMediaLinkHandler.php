<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Handlers;

use Modules\WebsiteCMS\SocialMediaLink\Commands\UpdateSocialMediaLinkCommand;
use Modules\WebsiteCMS\SocialMediaLink\Repositories\SocialMediaLinkRepository;

class UpdateSocialMediaLinkHandler
{
    public function __construct(
        private SocialMediaLinkRepository $repository,
    ) {
    }

    public function handle(UpdateSocialMediaLinkCommand $updateSocialMediaLinkCommand)
    {
        $this->repository->updateSocialMediaLink(
            $updateSocialMediaLinkCommand->getId(),
            $updateSocialMediaLinkCommand->toArray(),
            $updateSocialMediaLinkCommand->getIcon()
        );
    }
}
