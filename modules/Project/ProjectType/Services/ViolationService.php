<?php

namespace Modules\Project\ProjectType\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Repositories\ViolationRepository;

class ViolationService
{
    public function __construct(private ViolationRepository $repository) {}

    public function listAll(): Collection
    {
        return $this->repository->listOrderedByCode();
    }
}
