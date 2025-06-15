<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserAbout\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserAbout\Models\UserAbout;

/**
 * @property UserAbout $model
 * @method UserAbout findOneOrFail($id)
 * @method UserAbout findOneByOrFail(array $data)
 */
class UserAboutRepository extends BaseRepository
{
    public function __construct(UserAbout $model)
    {
        parent::__construct($model);
    }

    public function getUserAboutList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }

    public function getUserAbout(UuidInterface $companyId, UuidInterface $globalId): ?UserAbout
    {
        return $this->model->where([
            'global_id' => $globalId,
            'company_id' => $companyId,
        ])->first();
    }

    public function createUserAbout(array $data): UserAbout
    {
        return $this->create($data);
    }
    public function createOrUpdateUserAbout(array $data): UserAbout
    {
        $userAbout = $this->model->where([
            'global_id' => $data['global_id'],
            'company_id' => $data['company_id'],
        ])->first();

        if ($userAbout) {
            $userAbout->update($data);
            return $userAbout;
        }

        return $this->model->create($data);
    }

    public function updateUserAbout(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserAbout(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
