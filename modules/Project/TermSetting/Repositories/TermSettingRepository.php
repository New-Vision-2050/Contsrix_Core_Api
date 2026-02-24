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

    public function getTermSetting(UuidInterface $id): TermSetting
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTermSetting(array $data): TermSetting
    {
        return $this->create($data);
    }

    public function updateTermSetting(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTermSetting(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
