<?php

declare(strict_types=1);

namespace Modules\Shared\University\Filters;

use BasePackage\Shared\Filters\SearchModelFilter;

class UniversityFilter extends SearchModelFilter
{
    public $relations = [];

    public function name($name)
    {
        return $this->whereHas('translations',function($q) use ($name){
            $q->where('content','like','%'.$name.'%');
        });
    }

    public function country($id)
    {
        return $this->whereHas('country', function ($q) use ($id) {
            $q->where('id', $id);
        });
    }
}
