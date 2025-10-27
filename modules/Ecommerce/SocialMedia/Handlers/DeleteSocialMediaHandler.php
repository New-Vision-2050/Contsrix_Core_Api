<?php

declare(strict_types=1);

namespace Modules\Ecommerce\SocialMedia\Handlers;

use Modules\Ecommerce\SocialMedia\Repositories\SocialMediaRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteSocialMediaHandler
{
    public function __construct(
        private SocialMediaRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteSocialMedia($id);
    }
}
