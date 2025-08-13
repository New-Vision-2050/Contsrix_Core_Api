<?php

declare(strict_types=1);

namespace Modules\Program\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Program\Models\Program;

/**
 * @property Program $model
 * @method Program findOneOrFail($id)
 * @method Program findOneByOrFail(array $data)
 */
class ProgramRepository extends BaseRepository
{
    public function __construct(Program $model)
    {
        parent::__construct($model);
    }

    public function getProgramList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getProgram(UuidInterface $id): Program
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createProgram(array $data): Program
    {
        return $this->create($data);
    }

    public function updateProgram(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteProgram(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
