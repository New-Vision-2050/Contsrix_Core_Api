<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use App\Traits\HasExport;

/**
 * @property ProcedureSetting $model
 * @method ProcedureSetting findOneOrFail($id)
 * @method ProcedureSetting findOneByOrFail(array $data)
 */
class ProcedureSettingRepository extends BaseRepository
{
    use HasExport;

    public function __construct(ProcedureSetting $model)
    {
        parent::__construct($model);
    }

    public function getProcedureSettingList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProcedureSetting(UuidInterface $id): ProcedureSetting
    {
        return $this->model->with('steps.employee')->findOrFail($id->toString());
    }

    public function createProcedureSetting(array $data): ProcedureSetting
    {
        return $this->create($data);
    }

    public function updateProcedureSetting(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProcedureSetting(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
