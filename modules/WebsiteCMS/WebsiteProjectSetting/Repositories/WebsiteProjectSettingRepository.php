<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\WebsiteCMS\WebsiteProjectSetting\Models\WebsiteProjectSetting;
use App\Traits\HasExport;

/**
 * @property WebsiteProjectSetting $model
 * @method WebsiteProjectSetting findOneOrFail($id)
 * @method WebsiteProjectSetting findOneByOrFail(array $data)
 */
class WebsiteProjectSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(WebsiteProjectSetting $model)
    {
        parent::__construct($model);
    }

    public function getWebsiteProjectSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getWebsiteProjectSetting(UuidInterface $id): WebsiteProjectSetting
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createWebsiteProjectSetting(array $data): WebsiteProjectSetting
    {
        return $this->create($data);
    }

    public function updateWebsiteProjectSetting(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteWebsiteProjectSetting(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getAll(): Collection
    {
        return $this->model->query()->filter(request()->all())->get();
    }
}
