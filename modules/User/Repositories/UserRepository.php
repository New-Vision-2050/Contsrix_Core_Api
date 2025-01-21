<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\User\Models\User;

/**
 * @property User $model
 * @method User findOneOrFail($id)
 * @method User findOneByOrFail(array $data)
 */
class UserRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getUserList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUser(UuidInterface $id): User
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUser(array $data): User
    {
        return $this->create($data);
    }

    public function updateUser(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUser(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
