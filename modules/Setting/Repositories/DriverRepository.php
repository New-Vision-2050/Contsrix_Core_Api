<?php

declare(strict_types=1);

namespace Modules\Setting\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;

use Modules\Setting\Models\Driver;


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
        return $this->model->filter(request()->all())->get();
    }


    public function getDriverNamesByType($type)
    {
        return $this->model->where(["driver_type"=>$type])->pluck('name')->toArray();
    }
}
