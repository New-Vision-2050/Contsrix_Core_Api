<?php

declare(strict_types=1);

namespace Modules\Shared\TimeZone\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Shared\TimeZone\Models\TimeZone;

/**
 * @property TimeZone $model
 * @method TimeZone findOneOrFail($id)
 * @method TimeZone findOneByOrFail(array $data)
 */
class TimeZoneRepository extends BaseRepository
{
    public function __construct(TimeZone $model)
    {
        parent::__construct($model);
    }

    public function paginated(
        array $conditions = [],
        int $page = 1,
        int $perPage = 15,
        string $orderBy = 'created_at',
        string $sortBy = 'desc',
        ?\Closure $customQuery = null
    ) {
        if (method_exists($this->model, 'scopeFilter')) {
            $query = $this->model->filter(request()->all())->where($conditions);
        } else {
            $query = $this->model->where($conditions);
        }

        if ($customQuery) {
            $customQuery($query);
        }

        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->orderBy($orderBy, $sortBy)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);

        return [
            'pagination' => $paginationArray['pagination'],
            'data' => $paginatedData,
        ];
    }

    public function getTimeZone(UuidInterface $id): TimeZone
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createTimeZone(array $data): TimeZone
    {
        return $this->create($data);
    }

    public function updateTimeZone(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteTimeZone(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
