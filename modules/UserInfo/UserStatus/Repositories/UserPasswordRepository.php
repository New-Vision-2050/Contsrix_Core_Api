<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\User\Models\User;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserStatus\Models\UserStatus;

/**
 * @property User $model
 * @method UserStatus findOneOrFail($id)
 * @method UserStatus findOneByOrFail(array $data)
 */
class UserPasswordRepository extends BaseRepository
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getUserStatusList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUserStatus(UuidInterface $globalId): ?User
    {
        return $this->model->where([
            'global_id' => $globalId,
        ])->first();
    }

    public function createUserStatus(array $data): User
    {
        return $this->create($data);
    }

    public function updateUserStatus(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserStatus(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
