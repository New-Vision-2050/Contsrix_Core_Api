<?php

declare(strict_types=1);

namespace Modules\SubEntity\Repositories;

use Illuminate\Database\Eloquent\Collection;
use BasePackage\Shared\Repositories\BaseRepository;
use Modules\SubEntity\Models\RegistrationForm;

/**
 * @property RegistrationForm $model
 */
class RegistrationFormRepository extends BaseRepository
{
    public function __construct(RegistrationForm $model)
    {
        parent::__construct($model);
    }

    public function getRegistrationFormSelectionList(): Collection
    {
        return $this->all();
    }
}
