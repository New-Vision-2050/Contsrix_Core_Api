<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Services;

use Illuminate\Support\Collection;
use Modules\UserInfo\UserEducationalCourse\DTO\CreateUserEducationalCourseDTO;
use Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse;
use Modules\UserInfo\UserEducationalCourse\Repositories\UserEducationalCourseRepository;
use Ramsey\Uuid\UuidInterface;

class UserEducationalCourseCRUDService
{
    public function __construct(
        private UserEducationalCourseRepository $repository,
    ) {
    }

    public function create(CreateUserEducationalCourseDTO $createUserEducationalCourseDTO): UserEducationalCourse
    {
         return $this->repository->createUserEducationalCourse($createUserEducationalCourseDTO->toArray(), $createUserEducationalCourseDTO->file);
    }

    public function list(UuidInterface $companyId,UuidInterface $globalId,int $page = 1, int $perPage = 10): array
    {
        return $this->repository->getUserEducationalCourseList($companyId, $globalId, $page, $perPage);
    }

    public function get(UuidInterface $id): UserEducationalCourse
    {
        return $this->repository->getUserEducationalCourse(
            id: $id,
        );
    }
}
