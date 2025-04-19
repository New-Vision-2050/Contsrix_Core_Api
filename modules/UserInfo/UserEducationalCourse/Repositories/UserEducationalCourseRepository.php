<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Ramsey\Uuid\UuidInterface;
use Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse;

/**
 * @property UserEducationalCourse $model
 * @method UserEducationalCourse findOneOrFail($id)
 * @method UserEducationalCourse findOneByOrFail(array $data)
 */
class UserEducationalCourseRepository extends BaseRepository
{
    public function __construct(UserEducationalCourse $model)
    {
        parent::__construct($model);
    }

    public function getUserEducationalCourseList(UuidInterface $companyId, UuidInterface $globalId, ?int $page, ?int $perPage = 10)
    {
        return $this->paginated(
            ['company_id' => $companyId, 'global_id' => $globalId],
            $page,
            $perPage
        );
    }

    public function getUserEducationalCourse(UuidInterface $id): UserEducationalCourse
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ]);
    }

    public function createUserEducationalCourse(array $data): UserEducationalCourse
    {
        return $this->create($data);
    }

    public function updateUserEducationalCourse(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteUserEducationalCourse(UuidInterface $id): bool
    {
        return $this->delete($id);
    }
}
