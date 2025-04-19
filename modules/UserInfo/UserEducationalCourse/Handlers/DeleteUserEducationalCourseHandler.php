<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Handlers;

use Modules\UserInfo\UserEducationalCourse\Repositories\UserEducationalCourseRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteUserEducationalCourseHandler
{
    public function __construct(
        private UserEducationalCourseRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteUserEducationalCourse($id);
    }
}
