<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\CompanyUser\Models\CompanyUserAddress;

/**
 * @property CompanyUserAddress $model
 * @method CompanyUserAddress findOneOrFail($id)
 * @method CompanyUserAddress findOneByOrFail(array $data)
 */
class CompanyUserAddressRepository extends BaseRepository
{
    public function __construct(CompanyUserAddress $model)
    {
        parent::__construct($model);
    }

}
