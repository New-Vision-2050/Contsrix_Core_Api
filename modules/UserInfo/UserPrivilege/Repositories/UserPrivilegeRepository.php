<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;

/**
 * @property UserPrivilege $model
 * @method UserPrivilege findOneOrFail($id)
 * @method UserPrivilege findOneByOrFail(array $data)
 */
class UserPrivilegeRepository extends BaseRepository
{
    public function __construct(UserPrivilege $model)
    {
        parent::__construct($model);
    }

    public function getUserPrivilegeList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10):array
    {
        $result = $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );

        $result['data']->load([
            'privilege',
            'typePrivilege',
            'typeAllowance',
            'period',
            'medicalInsurance',
        ]);

        return $result;
    }

    public function getUserPrivilege(UuidInterface $id): UserPrivilege
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUserPrivilege(array $data): UserPrivilege
    {
        return $this->create($data);
    }

    public function updateUserPrivilege(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserPrivilege(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
