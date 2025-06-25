<?php

declare(strict_types=1);

namespace Modules\SubscriptionSystem\Modules\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\SubscriptionSystem\Modules\Models\Module;

/**
 * @property Module $model
 * @method Modules findOneOrFail($id)
 * @method Modules findOneByOrFail(array $data)
 */
class ModulesRepository extends BaseRepository
{
    public function __construct(Module $model)
    {
        parent::__construct($model);
    }

    public function getModulesList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getModules(UuidInterface $id): Module
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createModules(array $data): Module
    {
        return $this->create($data);
    }

    public function updateModules(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteModules(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function query()
    {
        return $this->model::query();
    }
}
