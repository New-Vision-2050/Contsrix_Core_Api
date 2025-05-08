<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserSalary\Models\UserSalary;

/**
 * @property UserSalary $model
 * @method UserSalary findOneOrFail($id)
 * @method UserSalary findOneByOrFail(array $data)
 */
class UserSalaryRepository extends BaseRepository
{
    public function __construct(UserSalary $model)
    {
        parent::__construct($model);
    }

    public function getUserSalaryList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUserSalary(UuidInterface $companyId, UuidInterface $globalId): ?UserSalary
    {
        return $this->model->where([
            'global_id' => $globalId,
            'company_id' => $companyId,
        ])->first();
    }

    public function createUserSalary(array $data): UserSalary
    {
        return $this->create($data);
    }

    public function createOrUpdateUserSalary(array $data): UserSalary
    {
        $userSalary = $this->model->where([
            'global_id' => $data['global_id'],
            'company_id' => $data['company_id'],
        ])->first();

        if ($userSalary) {
            $userSalary->update($data);
            return $userSalary;
        }

        return $this->model->create($data);
    }

    public function updateUserSalary(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserSalary(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
