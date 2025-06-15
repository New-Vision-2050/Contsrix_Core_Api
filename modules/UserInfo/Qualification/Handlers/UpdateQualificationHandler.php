<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Handlers;

use Modules\UserInfo\Qualification\Commands\UpdateQualificationCommand;
use Modules\UserInfo\Qualification\Repositories\QualificationRepository;

class UpdateQualificationHandler
{
    public function __construct(
        private QualificationRepository $repository,
    ) {
    }

    public function handle(UpdateQualificationCommand $updateQualificationCommand)
    {
        $this->repository->updateQualification($updateQualificationCommand->getId(), $updateQualificationCommand->toArray());
    }
}
