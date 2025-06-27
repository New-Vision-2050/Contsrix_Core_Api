<?php

declare(strict_types=1);

namespace Modules\Subscription\Module\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Subscription\Module\Models\Module;

/**
 * @property Module $model
 * @method Module findOneOrFail($id)
 * @method Module findOneByOrFail(array $data)
 */
class ModuleRepository extends BaseRepository
{
    public function __construct(Module $model)
    {
        parent::__construct($model);
    }

    public function getModuleList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getModule(UuidInterface $id): Module
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createModule(array $data): Module
    {
        return $this->create($data);
    }

    public function updateModule(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteModule(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
