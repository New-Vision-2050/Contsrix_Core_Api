<?php

declare(strict_types=1);

namespace Modules\JobTitle\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class JobTitleFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas('translations',function($q) use ($name){
            $q->where('content','like','%'.$name.'%');
        });
    }
    public function type($type)
    {
        return $this->where('type',$type);
    }
    public function company($company)
    {
        return $this->where('company_id',$company);
    }

    public function jobType($job_type_id)
    {
        return $this->where('job_type_id',$job_type_id);
    }

    
}
