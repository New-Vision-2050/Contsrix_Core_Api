<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserEducationalCourse\Handlers;

use Modules\UserInfo\UserEducationalCourse\Commands\UpdateUserEducationalCourseCommand;
use Modules\UserInfo\UserEducationalCourse\Repositories\UserEducationalCourseRepository;

class UpdateUserEducationalCourseHandler
{
    public function __construct(
        private UserEducationalCourseRepository $repository,
    ) {
    }

    public function handle(UpdateUserEducationalCourseCommand $updateUserEducationalCourseCommand)
    {
<<<<<<< HEAD
        $this->repository->updateUserEducationalCourse($updateUserEducationalCourseCommand->getId(), $updateUserEducationalCourseCommand->toArray(), $updateUserEducationalCourseCommand->file);
=======
        $this->repository->updateUserEducationalCourse($updateUserEducationalCourseCommand->getId(), $updateUserEducationalCourseCommand->toArray());
>>>>>>> 7be6c72c (merge with stage (first version ))
    }
}
