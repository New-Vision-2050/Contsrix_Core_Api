<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\CompanyUser\Models\ClientDetail;

/**
 * @property ClientDetail $model
 * @method ClientDetail findOneOrFail($id)
 * @method ClientDetail findOneByOrFail(array $data)
 */
class ClientDetailRepository extends BaseRepository
{
    public function __construct(ClientDetail $model)
    {
        parent::__construct($model);
    }



}
