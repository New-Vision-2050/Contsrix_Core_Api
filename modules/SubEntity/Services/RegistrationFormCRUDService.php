<?php

declare(strict_types=1);

namespace Modules\SubEntity\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\SubEntity\Repositories\RegistrationFormRepository;

class RegistrationFormCRUDService
{
    public function __construct(
        private RegistrationFormRepository $repository,
    ) {
    }

    public function getRegistrationFormSelectionList(): Collection
    {
        return $this->repository->all();
    }
}
