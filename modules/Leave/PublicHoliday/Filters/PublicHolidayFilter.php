<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class PublicHolidayFilter extends SearchModelFilter
{
    public $relations = [];

    public function search($name)
    {
        return $this->where('name', 'LIKE', '%' . $name . '%');
    }


    public function dateStart($date)
    {
        return $this->where('date_start',$date );
    }



    public function dateEnd($date)
    {
        return $this->where('date_end',$date );
    }


    public function country($country_id)
    {
        return $this->where('country_id',$country_id );
    }
}
