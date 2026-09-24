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
        [$month, $day] = explode('-', $date);
        return $this->whereMonth('date_start', $month)->whereDay('date_start', $day);
    }



    public function dateEnd($date)
    {
        [$month, $day] = explode('-', $date);
        return $this->whereMonth('date_end', $month)->whereDay('date_end', $day);
    }


    public function branch($branch_id)
    {
        return $this->where('branch_id',$branch_id );
    }
}
