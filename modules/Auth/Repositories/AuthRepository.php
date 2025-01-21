<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\Auth\Models\Auth;

/**
 * @property Auth $model
 * @method Auth findOneOrFail($id)
 * @method Auth findOneByOrFail(array $data)
 */
class AuthRepository extends BaseRepository
{
    public function __construct(Auth $model)
    {
        parent::__construct($model);
    }

    public function getAuthList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getAuth(UuidInterface $id): Auth
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createAuth(array $data): Auth
    {
        return $this->create($data);
    }

    public function updateAuth(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteAuth(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
