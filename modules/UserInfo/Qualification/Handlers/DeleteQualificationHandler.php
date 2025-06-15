<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Handlers;

use Modules\UserInfo\Qualification\Repositories\QualificationRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteQualificationHandler
{
    public function __construct(
        private QualificationRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteQualification($id);
    }
}
