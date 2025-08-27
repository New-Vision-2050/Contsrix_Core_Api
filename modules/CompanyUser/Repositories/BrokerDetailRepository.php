<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Modules\CompanyUser\Models\BrokerDetail;

/**
 * @property BrokerDetail $model
 * @method BrokerDetail findOneOrFail($id)
 * @method BrokerDetail findOneByOrFail(array $data)
 */
class BrokerDetailRepository extends BaseRepository
{
    public function __construct(BrokerDetail $model)
    {
        parent::__construct($model);
    }
}
