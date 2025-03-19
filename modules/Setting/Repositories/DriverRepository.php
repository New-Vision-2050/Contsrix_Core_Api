<?php

declare(strict_types=1);

namespace Modules\Setting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Models\Driver;
use Modules\Setting\Models\IdentifierSetting;
use Modules\Setting\Models\Setting;
use Ramsey\Uuid\UuidInterface;

class DriverRepository extends BaseRepository
{
    public function __construct(Driver $model)
    {
        parent::__construct($model);
    }



    public function getDataGroupByType()
    {
        return $this->all()->groupBy('driver_type');
    }

    public function getDrivers()
    {
        return $this->all();
    }


    public function getDriverNamesByType($type)
    {
        return $this->model->where(["driver_type"=>$type])->pluck('name')->toArray();
    }
}
