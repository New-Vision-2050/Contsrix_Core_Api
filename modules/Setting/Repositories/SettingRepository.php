<?php

declare(strict_types=1);

namespace Modules\Setting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Setting\Models\Setting;

class SettingRepository extends BaseRepository
{
    public function __construct(Setting $model)
    {
        parent::__construct($model);
    }

    public function getSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getSetting(string $key): Setting
    {
        return $this->findOneBy(['key' => $key]);
    }

    public function createSetting(array $data): Setting
    {
        return $this->updateOrCreate(['key' => $data['key']], $data);
    }

    public function deleteSetting(string $key): bool
    {
        return $this->findOneBy(['key' => $key])->delete();
    }
}
