<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserRelative\Models\UserRelative;

/**
 * @property UserRelative $model
 * @method UserRelative findOneOrFail($id)
 * @method UserRelative findOneByOrFail(array $data)
 */
class UserRelativeRepository extends BaseRepository
{
    public function __construct(UserRelative $model)
    {
        parent::__construct($model);
    }

    public function getUserRelativeList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUserRelative(UuidInterface $id): UserRelative
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUserRelative(array $data): UserRelative
    {
        return $this->create($data);
    }

    public function updateUserRelative(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserRelative(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
