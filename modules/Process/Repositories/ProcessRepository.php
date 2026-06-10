<?php

declare(strict_types=1);

namespace Modules\Process\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Process\Models\Process;
use App\Traits\HasExport;

/**
 * @property Process $model
 * @method Process findOneOrFail($id)
 * @method Process findOneByOrFail(array $data)
 */
class ProcessRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Process $model)
    {
        parent::__construct($model);
    }

    public function getProcessList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProcess(UuidInterface $id): Process
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createProcess(array $data): Process
    {
        return $this->create($data);
    }

    public function updateProcess(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProcess(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
