<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Project\TermSetting\Models\TermSetting;
use App\Traits\HasExport;

/**
 * @property TermSetting $model
 * @method TermSetting findOneOrFail($id)
 * @method TermSetting findOneByOrFail(array $data)
 */
class TermSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(TermSetting $model)
    {
        parent::__construct($model);
    }

    public function getTermSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTermSetting(int $id): TermSetting
    {
        return $this->model->findOrFail($id);
    }

    public function createTermSetting(array $data, array $termServicesIds = []): TermSetting
    {
        $termSetting = $this->create($data);

        if (!empty($termServicesIds)) {
            $termSetting->termServices()->sync($termServicesIds);
        }

        return $termSetting->fresh(['termServices']);
    }

    public function updateTermSetting(int $id, array $data, array $termServicesIds = []): bool
    {
        $termSetting = $this->model->findOrFail($id);
        $updated = $termSetting->update($data);

        if ($updated) {
            $termSetting->termServices()->sync($termServicesIds);
        }

        return $updated;
    }

    public function deleteTermSetting(int $id): bool
    {
        $termSetting = $this->model->findOrFail($id);
        return $termSetting->delete();
    }

    public function getTermSettingWithRelations(int $id): TermSetting
    {
        return $this->model
            ->with(['termServices', 'children', 'projectType'])
            ->findOrFail($id);
    }

    public function getTermSettingWithChildren(int $id): TermSetting
    {
        return $this->model
            ->with(['children', 'termServices', 'projectType'])
            ->findOrFail($id);
    }

    public function getTermSettingChildren(int $id): Collection
    {
        $termSetting = $this->model->findOrFail($id);

        return $termSetting->children()->with(['projectType'])->get();
    }
}
