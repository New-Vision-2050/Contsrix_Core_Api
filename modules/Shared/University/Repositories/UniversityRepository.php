<?php

declare(strict_types=1);

namespace Modules\Shared\University\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\University\Models\University;
use Ramsey\Uuid\UuidInterface;
/**
 * @property University $model
 * @method University findOneOrFail($id)
 * @method University findOneByOrFail(array $data)
 */
class UniversityRepository extends BaseRepository
{
    public function __construct(University $model)
    {
        parent::__construct($model);
    }

    public function getUniversityList(?int $page, ?int $perPage = 10): array
    {
        $items = $this->model->with('country')->orderBy('id', 'asc')->filter(request()->all())->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $items->items(),
            'pagination' => [
                'total' => $items->total(),
                'per_page' => $items->perPage(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
            ],
        ];
    }

    public function getUniversity(UuidInterface $id): University
    {
        return $this->model->with('country')->where('id', $id->toString())->firstOrFail();
    }

    public function createUniversity(array $data): University
    {
        return $this->create($data);
    }

    public function updateUniversity(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUniversity(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
