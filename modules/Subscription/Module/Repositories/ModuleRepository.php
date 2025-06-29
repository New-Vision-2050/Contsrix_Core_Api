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

    public function paginatedParents(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc'
    ) {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->where($conditions);
        } else {
            $query = $this->model->where($conditions);
        }

        $query->isParent()->with('children');

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->orderBy($orderBy, $sortBy)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }
}
