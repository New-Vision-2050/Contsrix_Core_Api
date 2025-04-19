<?php

declare(strict_types=1);

namespace Modules\UserInfo\Social\Services;

use Illuminate\Support\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\UserInfo\Social\DTO\CreateSocialDTO;
use Modules\UserInfo\Social\Models\Social;
use Modules\UserInfo\Social\Repositories\SocialRepository;
use Ramsey\Uuid\UuidInterface;

class SocialCRUDService
{
    public function __construct(
        private SocialRepository $repository,
    ) {
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): CompanyUser
    {
        return $this->repository->getSocial(
            id: $id,
        );
    }
}
