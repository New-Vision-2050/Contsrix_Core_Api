<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Handlers;

use Modules\Shared\SocialIcon\Repositories\SocialIconRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteSocialIconHandler
{
    public function __construct(
        private SocialIconRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteSocialIcon($id);
    }
}
