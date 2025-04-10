<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserBank\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserBank\Models\UserBank;

/**
 * @property UserBank $model
 * @method UserBank findOneOrFail($id)
 * @method UserBank findOneByOrFail(array $data)
 */
class UserBankRepository extends BaseRepository
{
    public function __construct(UserBank $model)
    {
        parent::__construct($model);
    }

    public function getUserBankList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUserBank(UuidInterface $id): UserBank
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUserBank(array $data): UserBank
    {
        return $this->create($data);
    }

    public function updateUserBank(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserBank(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
