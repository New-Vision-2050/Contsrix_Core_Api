<?php

declare(strict_types=1);

namespace Modules\Setting\Services;

use Modules\Setting\DTO\CreateSettingDTO;
use Modules\Setting\Models\Setting;
use Modules\Setting\Repositories\SettingRepository;

class SettingCRUDService
{
    public function __construct(
        private SettingRepository $repository,
    ) {
    }

    public function create(CreateSettingDTO $createSettingDTO): Setting
    {
         return $this->repository->createSetting($createSettingDTO->toArray());
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginated(
            page: $page,
            perPage: $perPage,
        );
    }

    public function all(): Setting
    {
        return $this->repository->all();
    }

    public function getValue($key)
    {
        return $this->repository->findOneBy(['key'=>$key])?->value;
    }
}
