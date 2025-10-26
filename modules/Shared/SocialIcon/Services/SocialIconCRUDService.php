<?php

declare(strict_types=1);

namespace Modules\Shared\SocialIcon\Services;

use Illuminate\Support\Collection;
use Modules\Shared\SocialIcon\DTO\CreateSocialIconDTO;
use Modules\Shared\SocialIcon\Models\SocialIcon;
use Modules\Shared\SocialIcon\Repositories\SocialIconRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;

class SocialIconCRUDService
{
    use HasExportService;

    public function __construct(
        private SocialIconRepository $repository,
    ) {
    }

    public function create(CreateSocialIconDTO $createSocialIconDTO): SocialIcon
    {
         return $this->repository->createSocialIcon($createSocialIconDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function get(UuidInterface $id): SocialIcon
    {
        return $this->repository->getSocialIcon(
            id: $id,
        );
    }
}
