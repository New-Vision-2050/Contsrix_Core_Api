<?php

declare(strict_types=1);

namespace Modules\Shared/Process\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared/Process\Models\Shared/Process;
use App\Traits\HasExport;

/**
 * @property Shared/Process $model
 * @method Shared/Process findOneOrFail($id)
 * @method Shared/Process findOneByOrFail(array $data)
 */
class Shared/ProcessRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Shared/Process $model)
    {
        parent::__construct($model);
    }

    public function getShared/ProcessList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getShared/Process(UuidInterface $id): Shared/Process
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createShared/Process(array $data): Shared/Process
    {
        return $this->create($data);
    }

    public function updateShared/Process(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteShared/Process(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
