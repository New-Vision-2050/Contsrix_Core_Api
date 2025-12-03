<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\SocialMediaLink\Handlers;

use Modules\WebsiteCMS\SocialMediaLink\Repositories\SocialMediaLinkRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteSocialMediaLinkHandler
{
    public function __construct(
        private SocialMediaLinkRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteSocialMediaLink($id);
    }
}
