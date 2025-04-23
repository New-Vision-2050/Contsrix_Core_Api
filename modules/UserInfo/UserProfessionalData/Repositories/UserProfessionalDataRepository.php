<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

/**
 * @property UserProfessionalData $model
 * @method UserProfessionalData findOneOrFail($id)
 * @method UserProfessionalData findOneByOrFail(array $data)
 */
class UserProfessionalDataRepository extends BaseRepository
{
    public function __construct(UserProfessionalData $model)
    {
        parent::__construct($model);
    }

    public function getUserProfessionalDataList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10)
    {
        return $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );
    }

    public function getUserProfessionalData(UuidInterface $id): UserProfessionalData
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUserProfessionalData(array $data): UserProfessionalData
    {
        return $this->create($data);
    }

    public function updateUserProfessionalData(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserProfessionalData(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
