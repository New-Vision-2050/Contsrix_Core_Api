<?php

declare(strict_types=1);

namespace Modules\Setting\Services;

use Faker\Core\Uuid;
use Modules\Setting\DTO\CreateSettingDTO;
use Modules\Setting\Models\Setting;
use Modules\Setting\Repositories\IdentifierSettingRepository;
use Modules\Setting\Repositories\SettingRepository;
use Ramsey\Uuid\UuidInterface;

class IdentifierSettingService
{
    public function __construct(
        private IdentifierSettingRepository $repository,
    ) {
    }


    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

}
