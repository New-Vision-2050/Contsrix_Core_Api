<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Handlers;

use Modules\Shared\ProfessionalBodie\Repositories\ProfessionalBodieRepository;
use Ramsey\Uuid\UuidInterface;

class DeleteProfessionalBodieHandler
{
    public function __construct(
        private ProfessionalBodieRepository $repository,
    ) {
    }

    public function handle(UuidInterface $id)
    {
        $this->repository->deleteProfessionalBodie($id);
    }
}
