<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserExperience\Models\UserExperience;

/**
 * @property UserExperience $model
 * @method UserExperience findOneOrFail($id)
 * @method UserExperience findOneByOrFail(array $data)
 */
class UserExperienceRepository extends BaseRepository
{
    public function __construct(UserExperience $model)
    {
        parent::__construct($model);
    }

    public function getUserExperienceList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10)
    {
        return $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );
    }

    public function getUserExperience(UuidInterface $id): UserExperience
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUserExperience(array $data): UserExperience
    {
        return $this->create($data);
    }

    public function updateUserExperience(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserExperience(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
