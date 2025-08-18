<?php

declare(strict_types=1);

namespace Modules\Test\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Ramsey\Uuid\UuidInterface;
use Modules\Test\Models\Test;
use App\Traits\HasExport;

/**
 * @property Test $model
 * @method Test findOneOrFail($id)
 * @method Test findOneByOrFail(array $data)
 */
class TestRepository extends BaseRepository
{
    use HasExport;

    public function __construct(Test $model)
    {
        parent::__construct($model);
    }

    public function getTestList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getTest(UuidInterface $id): Test
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTest(array $data): Test
    {
        return $this->create($data);
    }

    public function updateTest(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTest(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
